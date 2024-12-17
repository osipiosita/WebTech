document.addEventListener('DOMContentLoaded', function () {
    const verseText = document.getElementById('verse-text');
    const verseReference = document.getElementById('verse-reference');
    const favoriteBtn = document.getElementById('favorite-btn');
    let currentVerseId = null;

    // Fetch the verse of the day
    fetch('../actions/verse.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            verseText.textContent = `"${data.verse}"`;
            verseReference.textContent = `- ${data.reference}`;
            currentVerseId = data.verse_id; // Store the verse ID for favoriting

            // Check if this verse is already favorited
            checkFavoriteStatus(currentVerseId);
        })
        .catch(error => console.error('Error fetching verse:', error));

    // Favorite button functionality
    favoriteBtn.addEventListener('click', function() {
        if (!currentVerseId) {
            console.error('No verse ID available');
            return;
        }

        // Prepare the data to send
        const favoriteData = {
            verse_id: currentVerseId,
            verse_text: verseText.textContent.replace(/^"|"$/g, ''), // Remove quotes
            reference: verseReference.textContent.replace(/^- /, '') // Remove dash
        };

        console.log('Sending favorite data:', favoriteData);

        // Send request to add/remove favorite
        fetch('../actions/add_favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(favoriteData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Favorite response:', data);
            if (data.success) {
                if (data.action === 'added') {
                    favoriteBtn.classList.add('favorited');
                    favoriteBtn.textContent = 'Unfavorite Verse';
                } else {
                    favoriteBtn.classList.remove('favorited');
                    favoriteBtn.textContent = 'Favorite Verse';
                }
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // Function to check if the current verse is already favorited
    function checkFavoriteStatus(verseId) {
        fetch(`../actions/check_favorite.php?verse_id=${verseId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Favorite status:', data);
                if (data.is_favorited) {
                    favoriteBtn.classList.add('favorited');
                    favoriteBtn.textContent = 'Unfavorite Verse';
                }
            })
            .catch(error => console.error('Error checking favorite status:', error));
    }

    function createSnowflakes() {
        const numberOfSnowflakes = 50;
        const container = document.body;

        for (let i = 0; i < numberOfSnowflakes; i++) {
            const snowflake = document.createElement('div');
            snowflake.classList.add('snowflake');
            snowflake.textContent = '❄'; // Snowflake symbol
            snowflake.style.left = Math.random() * 100 + 'vw'; // Random horizontal position
            snowflake.style.fontSize = Math.random() * 1.5 + 1 + 'em'; // Random size
            snowflake.style.animationDuration = Math.random() * 5 + 5 + 's'; // Random speed
            snowflake.style.animationDelay = Math.random() * 5 + 's'; // Random delay
            container.appendChild(snowflake);
        }
    }

    createSnowflakes();
});