window.$ = window.$ || jQuery;

const MhubAdmin = {
    init() {
        this.bindEvents();
        this.changeBackground();
    },

    bindEvents() {
        $(document).on('mouseenter', '.mhub-rating .rating-wrapper .star', this.handleMouseEnter);
        $(document).on('mouseleave', '.mhub-rating .rating-wrapper .star', this.handleMouseLeave);
    },

    handleMouseEnter() {
        const ratingStars = $('.mhub-rating .rating-wrapper .star');
        const index = ratingStars.index(this);

        ratingStars.each(function(i) {
            if (i <= index) {
                $(this).addClass('filled'); // Add filled class to the hovered and previous stars
            }
        });
    },

    handleMouseLeave() {
        $('.mhub-rating .rating-wrapper .star').removeClass('filled'); // Remove filled class from all stars
    },

    changeBackground() {
        // Check if #wpwrap has the class directly
        const wpwrap = document.getElementById('wpwrap');
        if (wpwrap && wpwrap.classList.contains('mhub-admin-dasboard-bg')) {
            wpwrap.style.backgroundColor = '#f4f7ff';
            return;
        }
        // If not found, check descendants
        const mhubAdminDasboardBg = wpwrap ? wpwrap.querySelector('.mhub-admin-dasboard-bg') : null;
        if (mhubAdminDasboardBg) {
            wpwrap.style.backgroundColor = '#f4f7ff';
        }
    }
    
};

$(document).ready(function(){
    MhubAdmin.init();
});
