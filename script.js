const loader = document.getElementById('loader');

document.getElementById('generate-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const description = document.getElementById('description').value;

    if (!description) {
        alert('Please enter a description!');
        return;
    }

    // Show the loader before sending the request
    loader.style.display = 'block';

    try {
        // Make a POST request to the PHP backend
        const response = await fetch('generate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ description })
        });

        const data = await response.json();

        // Display the generated link
        const resultDiv = document.getElementById('result');
        // Get generated link (unique for the moment)
        let link = (data.links[0] ? data.links[0] : null);

        if (link) {
            resultDiv.innerHTML = `<a href="${link}" target="_blank">View Generated Page</a>`;
        } else {
            resultDiv.innerHTML = 'Error generating the page.';
        }
    } catch (error) {
        // Handle any potential errors
        resultDiv.innerHTML = 'An error occurred. Please try again.';
        console.error('Error:', error);
    } finally {
        // Hide the loader once the request is complete
        loader.style.display = 'none';
    }
});
