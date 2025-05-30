import {__} from '@wordpress/i18n';
import styles from './CreateCampaignModal.module.scss';
import CampaignFormModal from '../CampaignFormModal';
import {getGiveCampaignsListTableWindowData} from '../CampaignsListTable';

/**
 * Create Campaign Modal component
 *
 * @since 4.0.0
 */
export default function CreateCampaignModal({isOpen, setOpen}) {

    const openModal = () => setOpen(true);
    const closeModal = (response: ResponseProps = {}) => {
        setOpen(false);

        if (response?.id) {
            window.location.href =
                getGiveCampaignsListTableWindowData().adminUrl +
                'edit.php?post_type=give_forms&page=give-campaigns&id=' +
                response?.id;
        }
    };

    const apiSettings = getGiveCampaignsListTableWindowData();
    // Remove the /list-table from the apiRoot. This is a hack to make the API work while we don't refactor other list tables.
    apiSettings.apiRoot = apiSettings.apiRoot.replace('/list-table', '');

    return (
        <>
            <a style={{borderRadius: '4px'}} className={`button button-primary ${styles.createCampaignButton}`} onClick={openModal}>
                {__('Create campaign', 'give')}
            </a>
            <CampaignFormModal
                isOpen={isOpen}
                handleClose={closeModal}
                title={__('Create your campaign', 'give')}
                apiSettings={apiSettings}
            />
        </>
    );
}

type ResponseProps = {
    id?: string;
};
