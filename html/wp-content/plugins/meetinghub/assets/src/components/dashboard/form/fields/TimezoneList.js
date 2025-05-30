
const TimezoneList = () => {
    const timezoneArray = [
        { value: 'Pacific/Midway', label: '(GMT-11:00) Midway Island, Samoa ' },
        { value: 'Pacific/Pago_Pago', label: '(GMT-11:00) Pago Pago ' },
        { value: 'Pacific/Honolulu', label: '(GMT-10:00) Hawaii ' },
        { value: 'America/Anchorage', label: '(GMT-8:00) Alaska ' },
        { value: 'America/Vancouver', label: '(GMT-7:00) Vancouver ' },
        { value: 'America/Los_Angeles', label: '(GMT-7:00) Pacific Time (US and Canada) ' },
        { value: 'America/Tijuana', label: '(GMT-7:00) Tijuana ' },
        { value: 'America/Phoenix', label: '(GMT-7:00) Arizona ' },
        { value: 'America/Edmonton', label: '(GMT-6:00) Edmonton ' },
        { value: 'America/Denver', label: '(GMT-6:00) Mountain Time (US and Canada) ' },
        { value: 'America/Mazatlan', label: '(GMT-6:00) Mazatlan ' },
        { value: 'America/Regina', label: '(GMT-6:00) Saskatchewan ' },
        { value: 'America/Guatemala', label: '(GMT-6:00) Guatemala ' },
        { value: 'America/El_Salvador', label: '(GMT-6:00) El Salvador ' },
        { value: 'America/Managua', label: '(GMT-6:00) Managua ' },
        { value: 'America/Costa_Rica', label: '(GMT-6:00) Costa Rica ' },
        { value: 'America/Tegucigalpa', label: '(GMT-6:00) Tegucigalpa ' },
        { value: 'America/Winnipeg', label: '(GMT-5:00) Winnipeg ' },
        { value: 'America/Chicago', label: '(GMT-5:00) Central Time (US and Canada) ' },
        { value: 'America/Mexico_City', label: '(GMT-5:00) Mexico City ' },
        { value: 'America/Panama', label: '(GMT-5:00) Panama ' },
        { value: 'America/Bogota', label: '(GMT-5:00) Bogota ' },
        { value: 'America/Lima', label: '(GMT-5:00) Lima ' },
        { value: 'America/Caracas', label: '(GMT-4:30) Caracas ' },
        { value: 'America/Montreal', label: '(GMT-4:00) Montreal ' },
        { value: 'America/New_York', label: '(GMT-4:00) Eastern Time (US and Canada) ' },
        { value: 'America/Indianapolis', label: '(GMT-4:00) Indiana (East) ' },
        { value: 'America/Puerto_Rico', label: '(GMT-4:00) Puerto Rico ' },
        { value: 'America/Santiago', label: '(GMT-4:00) Santiago ' },
        { value: 'America/Halifax', label: '(GMT-3:00) Halifax ' },
        { value: 'America/Montevideo', label: '(GMT-3:00) Montevideo ' },
        { value: 'America/Araguaina', label: '(GMT-3:00) Brasilia ' },
        { value: 'America/Argentina/Buenos_Aires', label: '(GMT-3:00) Buenos Aires, Georgetown ' },
        { value: 'America/Sao_Paulo', label: '(GMT-3:00) Sao Paulo ' },
        { value: 'Canada/Atlantic', label: '(GMT-3:00) Atlantic Time (Canada) ' },
        { value: 'America/St_Johns', label: '(GMT-2:30) Newfoundland and Labrador ' },
        { value: 'America/Godthab', label: '(GMT-2:00) Greenland ' },
        { value: 'Atlantic/Cape_Verde', label: '(GMT-1:00) Cape Verde Islands ' },
        { value: 'Atlantic/Azores', label: '(GMT+0:00) Azores ' },
        { value: 'UTC', label: '(GMT+0:00) Universal Time UTC ' },
        { value: 'Etc/Greenwich', label: '(GMT+0:00) Greenwich Mean Time ' },
        { value: 'Atlantic/Reykjavik', label: '(GMT+0:00) Reykjavik ' },
        { value: 'Africa/Nouakchott', label: '(GMT+0:00) Nouakchott ' },
        { value: 'Europe/Dublin', label: '(GMT+1:00) Dublin ' },
        { value: 'Europe/London', label: '(GMT+1:00) London ' },
        { value: 'Europe/Lisbon', label: '(GMT+1:00) Lisbon ' },
        { value: 'Africa/Casablanca', label: '(GMT+1:00) Casablanca ' },
        { value: 'Africa/Bangui', label: '(GMT+1:00) West Central Africa ' },
        { value: 'Africa/Algiers', label: '(GMT+1:00) Algiers ' },
        { value: 'Africa/Tunis', label: '(GMT+1:00) Tunis ' },
        { value: 'Europe/Belgrade', label: '(GMT+2:00) Belgrade, Bratislava, Ljubljana ' },
        { value: 'CET', label: '(GMT+2:00) Sarajevo, Skopje, Zagreb ' },
        { value: 'Europe/Oslo', label: '(GMT+2:00) Oslo ' },
        { value: 'Europe/Copenhagen', label: '(GMT+2:00) Copenhagen ' },
        { value: 'Europe/Brussels', label: '(GMT+2:00) Brussels ' },
        { value: 'Europe/Berlin', label: '(GMT+2:00) Amsterdam, Berlin, Rome, Stockholm, Vienna ' },
        { value: 'Europe/Amsterdam', label: '(GMT+2:00) Amsterdam ' },
        { value: 'Europe/Rome', label: '(GMT+2:00) Rome ' },
        { value: 'Europe/Stockholm', label: '(GMT+2:00) Stockholm ' },
        { value: 'Europe/Vienna', label: '(GMT+2:00) Vienna ' },
        { value: 'Europe/Luxembourg', label: '(GMT+2:00) Luxembourg ' },
        { value: 'Europe/Paris', label: '(GMT+2:00) Paris ' },
        { value: 'Europe/Zurich', label: '(GMT+2:00) Zurich ' },
        { value: 'Europe/Madrid', label: '(GMT+2:00) Madrid ' },
        { value: 'Africa/Harare', label: '(GMT+2:00) Harare, Pretoria ' },
        { value: 'Europe/Warsaw', label: '(GMT+2:00) Warsaw ' },
        { value: 'Europe/Prague', label: '(GMT+2:00) Prague Bratislava ' },
        { value: 'Europe/Budapest', label: '(GMT+2:00) Budapest ' },
        { value: 'Africa/Tripoli', label: '(GMT+2:00) Tripoli ' },
        { value: 'Africa/Cairo', label: '(GMT+2:00) Cairo ' },
        { value: 'Africa/Johannesburg', label: '(GMT+2:00) Johannesburg ' },
        { value: 'Europe/Helsinki', label: '(GMT+3:00) Helsinki ' },
        { value: 'Africa/Nairobi', label: '(GMT+3:00) Nairobi ' },
        { value: 'Europe/Sofia', label: '(GMT+3:00) Sofia ' },
        { value: 'Europe/Istanbul', label: '(GMT+3:00) Istanbul ' },
        { value: 'Europe/Athens', label: '(GMT+3:00) Athens ' },
        { value: 'Europe/Bucharest', label: '(GMT+3:00) Bucharest ' },
        { value: 'Asia/Nicosia', label: '(GMT+3:00) Nicosia ' },
        { value: 'Asia/Beirut', label: '(GMT+3:00) Beirut ' },
        { value: 'Asia/Damascus', label: '(GMT+3:00) Damascus ' },
        { value: 'Asia/Jerusalem', label: '(GMT+3:00) Jerusalem ' },
        { value: 'Asia/Amman', label: '(GMT+3:00) Amman ' },
        { value: 'Europe/Moscow', label: '(GMT+3:00) Moscow ' },
        { value: 'Asia/Baghdad', label: '(GMT+3:00) Baghdad ' },
        { value: 'Asia/Kuwait', label: '(GMT+3:00) Kuwait ' },
        { value: 'Asia/Riyadh', label: '(GMT+3:00) Riyadh ' },
        { value: 'Asia/Bahrain', label: '(GMT+3:00) Bahrain ' },
        { value: 'Asia/Qatar', label: '(GMT+3:00) Qatar ' },
        { value: 'Asia/Aden', label: '(GMT+3:00) Aden ' },
        { value: 'Africa/Khartoum', label: '(GMT+3:00) Khartoum ' },
        { value: 'Africa/Djibouti', label: '(GMT+3:00) Djibouti ' },
        { value: 'Africa/Mogadishu', label: '(GMT+3:00) Mogadishu ' },
        { value: 'Europe/Kiev', label: '(GMT+3:00) Kiev ' },
        { value: 'Asia/Dubai', label: '(GMT+4:00) Dubai ' },
        { value: 'Asia/Muscat', label: '(GMT+4:00) Muscat ' },
        { value: 'Asia/Tehran', label: '(GMT+4:30) Tehran ' },
        { value: 'Asia/Kabul', label: '(GMT+4:30) Kabul ' },
        { value: 'Asia/Baku', label: '(GMT+5:00) Baku, Tbilisi, Yerevan ' },
        { value: 'Asia/Yekaterinburg', label: '(GMT+5:00) Yekaterinburg ' },
        { value: 'Asia/Tashkent', label: '(GMT+5:00) Islamabad, Karachi, Tashkent ' },
        { value: 'Asia/Calcutta', label: '(GMT+5:30) India ' },
        { value: 'Asia/Kolkata', label: '(GMT+5:30) Mumbai, Kolkata, New Delhi ' },
        { value: 'Asia/Kathmandu', label: '(GMT+5:45) Kathmandu ' },
        { value: 'Asia/Novosibirsk', label: '(GMT+6:00) Novosibirsk ' },
        { value: 'Asia/Almaty', label: '(GMT+6:00) Almaty ' },
        { value: 'Asia/Dacca', label: '(GMT+6:00) Dacca ' },
        { value: 'Asia/Dhaka', label: '(GMT+6:00) Astana, Dhaka ' },
        { value: 'Asia/Krasnoyarsk', label: '(GMT+7:00) Krasnoyarsk ' },
        { value: 'Asia/Bangkok', label: '(GMT+7:00) Bangkok ' },
        { value: 'Asia/Saigon', label: '(GMT+7:00) Vietnam ' },
        { value: 'Asia/Jakarta', label: '(GMT+7:00) Jakarta ' },
        { value: 'Asia/Irkutsk', label: '(GMT+8:00) Irkutsk, Ulaanbaatar ' },
        { value: 'Asia/Shanghai', label: '(GMT+8:00) Beijing, Shanghai ' },
        { value: 'Asia/Hong_Kong', label: '(GMT+8:00) Hong Kong ' },
        { value: 'Asia/Taipei', label: '(GMT+8:00) Taipei ' },
        { value: 'Asia/Kuala_Lumpur', label: '(GMT+8:00) Kuala Lumpur ' },
        { value: 'Asia/Singapore', label: '(GMT+8:00) Singapore ' },
        { value: 'Australia/Perth', label: '(GMT+8:00) Perth ' },
        { value: 'Asia/Yakutsk', label: '(GMT+9:00) Yakutsk ' },
        { value: 'Asia/Seoul', label: '(GMT+9:00) Seoul ' },
        { value: 'Asia/Tokyo', label: '(GMT+9:00) Osaka, Sapporo, Tokyo ' },
        { value: 'Australia/Darwin', label: '(GMT+9:30) Darwin ' },
        { value: 'Australia/Adelaide', label: '(GMT+9:30) Adelaide ' },
        { value: 'Asia/Vladivostok', label: '(GMT+10:00) Vladivostok ' },
        { value: 'Pacific/Port_Moresby', label: '(GMT+10:00) Guam, Port Moresby ' },
        { value: 'Australia/Brisbane', label: '(GMT+10:00) Brisbane ' },
        { value: 'Australia/Sydney', label: '(GMT+10:00) Canberra, Melbourne, Sydney '},
        { value: 'Australia/Hobart', label: '(GMT+10:00) Hobart '},
        { value:' Asia/Magadan', label: '(GMT+10:00) Magadan '},
        { value: 'SST', label:'(GMT+11:00) Solomon Islands '},
        { value: 'Pacific/Noumea', label: '(GMT+11:00) New Caledonia '},
        { value: 'Asia/Kamchatka', label: '(GMT+12:00) Kamchatka '},
        { value: 'Pacific/Fiji', label: '(GMT+12:00) Fiji Islands, Marshall Islands '},
        { value: 'Pacific/Auckland', label: '(GMT+12:00) Auckland, Wellington'},
    ]
  
    return timezoneArray;
};
  
export default TimezoneList;

