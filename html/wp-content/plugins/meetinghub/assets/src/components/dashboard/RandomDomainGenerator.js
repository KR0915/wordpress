
let shuffledDomains = null;

export function getRandomDomain() {
    if (shuffledDomains === null) {
        const domains = [
            // 'webconf.viviers-fibre.net',
            // 'jitsi.member.fsf.org',
            'jitsi.riot.im',
			'meet.tedomum.net',
			'meet.evolix.org',
			'video.devloprog.org',
			'meet.ffmuc.net',
        ];

        shuffleArray(domains);
        shuffledDomains = domains;
    }

    const randomIndex = Math.floor(Math.random() * shuffledDomains.length);
    return shuffledDomains[randomIndex];
}

function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
}
