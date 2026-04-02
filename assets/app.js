document.addEventListener('DOMContentLoaded', () => {
    // ==========================================
    // Theme Toggle Logic
    // ==========================================
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const htmlEl = document.documentElement;
    const bodyEl = document.body;

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = htmlEl.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            // Update DOM
            htmlEl.setAttribute('data-theme', newTheme);
            bodyEl.className = `theme-${newTheme}`;

            // Update Icon
            const icon = themeToggleBtn.querySelector('i');
            if (newTheme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }

            // Save theme preference via AJAX (silently fails if not logged in or endpoint missing)
            fetch('api/update_theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `theme=${encodeURIComponent(newTheme)}`
            }).catch(err => console.error('Error saving theme:', err));
        });
    }

    // ==========================================
    // Profile Dropdown Logic
    // ==========================================
    const profileBtn = document.querySelector('.profile-dropdown-btn');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    if (profileBtn && dropdownMenu) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
    }

    // ==========================================
    // Voting Logic (Upvote/Downvote)
    // ==========================================
    const voteButtons = document.querySelectorAll('.vote-btn');

    voteButtons.forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();

            const postId = this.dataset.id;
            const type = this.dataset.type; // 'up' or 'down'
            const isUpvote = type === 'up';

            const container = this.closest('.post-vote-col');
            const upvoteBtn = container.querySelector('.upvote');
            const downvoteBtn = container.querySelector('.downvote');
            const countEl = container.querySelector('.vote-count');

            let currentCount = parseInt(countEl.textContent, 10);
            if (isNaN(currentCount)) currentCount = 0;

            // Optimistic UI Update
            if (isUpvote) {
                if (this.classList.contains('upvoted')) {
                    // Remove upvote
                    this.classList.remove('upvoted');
                    currentCount--;
                } else {
                    // Add upvote
                    this.classList.add('upvoted');
                    currentCount++;
                    if (downvoteBtn.classList.contains('downvoted')) {
                        downvoteBtn.classList.remove('downvoted');
                        currentCount++; // Recover the downvote subtraction
                    }
                }
            } else {
                if (this.classList.contains('downvoted')) {
                    // Remove downvote
                    this.classList.remove('downvoted');
                    currentCount++;
                } else {
                    // Add downvote
                    this.classList.add('downvoted');
                    currentCount--;
                    if (upvoteBtn.classList.contains('upvoted')) {
                        upvoteBtn.classList.remove('upvoted');
                        currentCount--; // Recover the upvote addition
                    }
                }
            }

            countEl.textContent = currentCount;

            // Send AJAX request to server
            try {
                const response = await fetch('api/vote.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `post_id=${postId}&type=${type}`
                });

                const data = await response.json();
                if (!data.success) {
                    console.error('Vote failed:', data.message);
                    // Revert UI if needed in a real robust app
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    // Server returned accurate count, sync it
                    countEl.textContent = data.new_count;
                }
            } catch (error) {
                console.error('Voting error:', error);
            }
        });
    });
});
