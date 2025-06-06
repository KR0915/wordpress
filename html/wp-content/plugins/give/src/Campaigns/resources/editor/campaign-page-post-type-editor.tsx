import { registerPlugin } from '@wordpress/plugins';
import {
    __experimentalFullscreenModeClose as FullscreenModeClose,
    __experimentalMainDashboardButton as MainDashboardButton,
    // @ts-ignore
} from '@wordpress/edit-post';

declare const window: {
    giveCampaignPage: {
        campaignDetailsURL: string;
    };
} & Window;

registerPlugin( 'campaign-page-editor-back-button', {
    render: () =>  (
        <MainDashboardButton>
            <FullscreenModeClose
                icon={ GiveLogo }
                href={window.giveCampaignPage.campaignDetailsURL}
                showTooltip={false} // Note: There is not a prop to customize the tooltip text, so we hide it.
            />
        </MainDashboardButton>
    )
} );

const GiveLogo = function () {
    return (
        <svg width="36" height="36" viewBox="0 0 130 131" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clipPath="url(#clip0)">
                <path
                    d="M130 65.8535C130 29.9689 100.885 0.853516 65 0.853516C29.1154 0.853516 0 29.9689 0 65.8535C0 101.738 29.1154 130.854 65 130.854C100.885 130.854 130 101.738 130 65.8535Z"
                    fill="#66BB6A" />
                <mask id="mask0" mask-type="alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="130" height="131">
                    <path
                        d="M130 65.8535C130 29.9689 100.885 0.853516 65 0.853516C29.1154 0.853516 0 29.9689 0 65.8535C0 101.738 29.1154 130.854 65 130.854C100.885 130.854 130 101.738 130 65.8535Z"
                        fill="black" />
                </mask>
                <g mask="url(#mask0)">
                    <path
                        d="M74.2303 70.4697C74.4995 71.0466 75.038 71.5466 75.038 71.5466C86.538 72.9697 102.807 71.3927 115.23 69.7004C108.115 84.9697 95.4226 95.2004 83.6919 95.2004C61.8457 95.2004 44.9226 68.662 44.9226 68.662C51.6919 62.662 62.8842 43.162 79.038 43.162C95.1919 43.162 102.192 52.0081 102.192 52.0081L104 49.162C104 49.162 96.4611 22.7773 75.1149 22.7773C53.7688 22.7773 31.1149 57.7389 17.8457 65.8158C17.8457 65.8158 36.0765 109.008 75.8842 109.008C109.346 109.008 117.73 77.0466 119.307 69.1235C123.769 68.4697 127.461 67.8158 129.923 67.3927C130.73 65.5466 131.653 62.3543 130.961 58.085C117.73 63.162 97.538 68.9312 73.8457 68.9312C73.8457 68.9312 73.9226 69.7389 74.2303 70.4697Z"
                        fill="white" />
                </g>
            </g>
            <defs>
                <clipPath id="clip0">
                    <path d="M0 0.853516H130V130.854H0V0.853516Z" fill="white" />
                </clipPath>
            </defs>
        </svg>
    );
};

