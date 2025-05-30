<?php
/**
 * Mhub_Events
 *
 * Manages meetings
 *
 * @package SOVLIX\MHUB
 */

namespace SOVLIX\MHUB\Googlemeet;

if ( ! class_exists( 'Mhub_Events' ) ) {
	/**
	 * Class Mhub_Events
	 *
	 * Handles Google Meet event management.
	 */
	class Mhub_Events {

		/**
		 * Google Client instance.
		 *
		 * @var GoogleEvent
		 */
		protected $google_client;

		/**
		 * Unauthorized app message.
		 *
		 * @var string
		 */
		protected $unauthorized_msg = 'Your app is not authorized. Please authorize from the set-api page.';

		/**
		 * Constructor to initialize Google Client.
		 */
		public function __construct() {
			$this->google_client = Mhub_Meet::get_instance();
			if ( $this->google_client->client && ! $this->google_client->service ) {
				try {
					$this->google_client->service = new \Google_Service_Calendar( $this->google_client->client );
				} catch ( \Throwable $e ) {
					$error = new WP_Error( 'google_service_error', 'Error initializing Google Service', array( 'error_message' => $e->getMessage() ) );
				}
			}
		}

		/**
		 * Creates a Google Meet event.
		 *
		 * @param array $data Event data including title, summary, attendees, and other details.
		 * @return array|\Google_Service_Calendar_Event Created event details or error message.
		 */
		public function create_meeting( array $data ) {

			if ( ! $this->google_client->is_app_permitted() ) {
				return array( 'error' => $this->unauthorized_msg );
			}

			try {
				$event = new \Google_Service_Calendar_Event( $data );

				$event = $this->google_client->service->events->insert(
					$this->google_client->current_calendar,
					$event,
					array( 'conferenceDataVersion' => 1 )
				);

				return $event;
			} catch ( \Throwable $e ) {
				return array( 'error' => $e->getMessage() );
			}
		}

		/**
		 * Updates an existing Google Calendar event with new details.
		 *
		 * @param string $event_id The ID of the event to update.
		 * @param string $title    The new title for the event.
		 * @param array  $data     The event details to update (e.g., time, attendees, visibility).
		 *
		 * @return array|\Google_Service_Calendar_Event Updated event or an error array.
		 *
		 * @throws \Exception If Google Client or Service is not initialized.
		 */
		public function update_meeting( string $event_id, $title, array $data ) {

			try {

				$this->validate_google_service();

				// Fetch the existing event.
				$event = $this->google_client->service->events->get(
					$this->google_client->current_calendar,
					$event_id
				);

				$event->setSummary( $title );

				// Update title.
				if ( ! empty( $title ) ) {
					$event->setSummary( $title );
				}

				// Update title/summary.
				if ( ! empty( $data['meeting_summary'] ) ) {
					$event->setDescription( $data['meeting_summary'] );
				}

				// Update start and end times.
				$event->setStart(
					new \Google_Service_Calendar_EventDateTime(
						array(
							'dateTime' => $data['startDateTime'],
							'timeZone' => $data['meeting_timezone'],
						)
					)
				);

				// Calculate and set end time.
				$end_date_time = ( new \DateTime( $data['startDateTime'] ) )
					->add( new \DateInterval( 'PT' . $data['duration_hours'] . 'H' . $data['duration_minutes'] . 'M' ) )
					->format( \DateTime::ATOM );

				$event->setEnd(
					new \Google_Service_Calendar_EventDateTime(
						array(
							'dateTime' => $end_date_time,
							'timeZone' => $data['meeting_timezone'],
						)
					)
				);

				// Update attendees (if provided).
				if ( ! empty( $data['attendees'] ) ) {
					$attendees = array_map(
						function ( $email ) {
							return array( 'email' => $email );
						},
						$data['attendees']
					);
					$event->setAttendees( $attendees );
				}

				// Update reminders (if provided).
				if ( ! empty( $data['reminder_time'] ) ) {
					$event->setReminders(
						new \Google_Service_Calendar_EventReminders(
							array(
								'useDefault' => false,
								'overrides'  => array(
									array(
										'method'  => 'popup',
										'minutes' => $data['reminder_time'],
									),
								),
							)
						)
					);
				}

				// Update visibility.
				if ( ! empty( $data['event_visibility'] ) ) {
					$event->setVisibility( $data['event_visibility'] );
				}

				// Update event status.
				if ( ! empty( $data['event_status'] ) ) {
					$event->setStatus( $data['event_status'] );
				}

				// Optionally handle transparency.
				if ( ! empty( $data['transparency'] ) ) {
					$event->setTransparency( $data['transparency'] );
				}

				// Update the event in the calendar.
				return $this->google_client->service->events->update(
					$this->google_client->current_calendar,
					$event->getId(),
					$event
				);
			} catch ( \Throwable $e ) {
				return array( 'error' => $e->getMessage() );
			}
		}

		/**
		 * Fetches a Google Meet event by its ID.
		 *
		 * @param string $meeting_id The ID of the meeting to fetch.
		 * @return array|\Google_Service_Calendar_Event Event details or error message.
		 */
		public function mhub_get_meeting( string $meeting_id ) {
			try {
				// Validate Google Client and Service initialization.
				$this->validate_google_service();

				// Fetch the meeting event by ID.
				$event = $this->google_client->service->events->get(
					$this->google_client->current_calendar,
					$meeting_id
				);

				return $event;
			} catch ( \Throwable $e ) {
				return array( 'error' => $e->getMessage() );
			}
		}

		/**
		 * Fetches all meetings from the user's Google Calendar.
		 *
		 * @return array|WP_Error List of meetings or error details.
		 */
		public function get_all_meetings() {
			try {
				// Validate Google Client and Service initialization.
				$this->validate_google_service();
				// Initialize an array to store all events.
				$all_events = array();

				// Set the initial parameters for fetching events.
				$params = array(
					'calendarId'   => $this->google_client->current_calendar,
					'singleEvents' => true, // Ensure recurring events are split into single instances.
					'maxResults'   => 100,  // Fetch 100 events per page (Google API limit).
				);

				// Fetch all events using pagination.
				do {
					$events = $this->google_client->service->events->listEvents(
						$params['calendarId'],
						$params
					);

					// Add the fetched events to the list.
					$all_events = array_merge( $all_events, $events->getItems() );

					// Update the page token for the next iteration.
					$params['pageToken'] = $events->getNextPageToken();
				} while ( ! empty( $params['pageToken'] ) );

				// Sort all events by creation time in descending order.
				usort(
					$all_events,
					function ( $a, $b ) {
						$created_a = strtotime( $a['created'] );
						$created_b = strtotime( $b['created'] );
						return $created_b <=> $created_a; // Descending order.
					}
				);

				return $all_events;
			} catch ( \Throwable $e ) {
				// Return error details in case of failure.
				return new \WP_Error( 'get_meetings_error', $e->getMessage() );
			}
		}

		/**
		 * Validates that the Google Client and Service are properly initialized.
		 *
		 * @throws \Exception If the Google Client or Service is not initialized.
		 */
		private function validate_google_service() {
			if ( empty( $this->google_client->client ) || empty( $this->google_client->service ) ) {
				throw new \Exception( 'Google Client or Service is not initialized.' );
			}
		}

		/**
		 * Deletes a Google Meet event.
		 *
		 * @param string $event_id Event ID to delete.
		 * @return bool|array True if successful, or error message.
		 */
		public function delete_meeting( string $event_id ) {
			try {
				$this->google_client->service->events->delete(
					$this->google_client->current_calendar,
					$event_id
				);
				return true;
			} catch ( \Throwable $e ) {
				return array( 'error' => $e->getMessage() );
			}
		}

		/**
		 * Fetches all instances of a recurring Google Meet event.
		 *
		 * @param string $meeting_id The ID of the recurring meeting to fetch instances for.
		 * @return array|\Google_Service_Calendar_Events List of meeting instances or error message.
		 */
		public function mhub_get_meeting_instances( string $meeting_id ) {
			try {
				// Validate Google Client and Service initialization.
				$this->validate_google_service();

				// Fetch all instances of the recurring meeting by event ID.
				$instances = $this->google_client->service->events->instances(
					$this->google_client->current_calendar,
					$meeting_id
				);

				return $instances;
			} catch ( \Throwable $e ) {
				return array( 'error' => $e->getMessage() );
			}
		}

		/**
		 * Registers an attendee for an existing Google Meet event.
		 *
		 * @param string $event_id   The ID of the meeting to add the attendee to.
		 * @param string $email      The email address of the attendee.
		 * @param string $name       The name of the attendee (optional).
		 * @param bool   $notify     Whether to send email notification to the attendee (default: true).
		 * @return array|\Google_Service_Calendar_Event Updated event details or error message.
		 */
		public function register_attendee( string $event_id, string $email, string $name = '', bool $notify = true ) {
			try {
				// Validate Google Client and Service initialization.
				$this->validate_google_service();

				if ( ! $this->google_client->is_app_permitted() ) {
					return array( 'error' => $this->unauthorized_msg );
				}

				// Fetch the existing event.
				$event = $this->google_client->service->events->get(
					$this->google_client->current_calendar,
					$event_id
				);

				// Get current attendees.
				$current_attendees = $event->getAttendees();
				if ( null === $current_attendees ) {
					$current_attendees = array();
				}

				// Check if the attendee is already registered.
				foreach ( $current_attendees as $attendee ) {
					if ( $attendee->getEmail() === $email ) {
						return array( 'error' => 'Attendee is already registered for this meeting' );
					}
				}

				// Create the new attendee.
				$new_attendee = new \Google_Service_Calendar_EventAttendee();
				$new_attendee->setEmail( $email );

				// Set name if provided.
				if ( ! empty( $name ) ) {
					$new_attendee->setDisplayName( $name );
				}

				// Add to current attendees list.
				$current_attendees[] = $new_attendee;
				$event->setAttendees( $current_attendees );

				// Update the event in the calendar.
				return $this->google_client->service->events->update(
					$this->google_client->current_calendar,
					$event_id,
					$event,
					array(
						// Set sendUpdates parameter to control notifications.
						'sendUpdates' => $notify ? 'all' : 'none',
					)
				);
			} catch ( \Throwable $e ) {
				return array( 'error' => $e->getMessage() );
			}
		}

		/**
		 * Resets Google Meet credentials.
		 *
		 * @return bool True if credentials are successfully reset, otherwise false.
		 */
		public function reset_credential() {
			$file_name = 'credential.json';
			$file_path = \trailingslashit( wp_upload_dir()['basedir'] ) . 'googlemeet-json/' . $file_name;

			if ( file_exists( $file_path ) ) {
				return wp_delete_file( $file_path ) ? true : false;
			}

			return false;
		}
	}
}
