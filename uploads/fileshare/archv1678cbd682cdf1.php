<?php
// Function to fetch artwork IDs from Met API
function getArtworkIds($page = 1, $limit = 20) {
    $url = "https://collectionapi.metmuseum.org/public/collection/v1/search?q=painting&hasImages=true&medium=Paintings";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response);
    if (!$data) return null;
    
    // Calculate pagination
    $start = ($page - 1) * $limit;
    return array_slice($data->objectIDs, $start, $limit);
}

// Function to fetch artwork details
function getArtworkDetails($objectId) {
    $url = "https://collectionapi.metmuseum.org/public/collection/v1/objects/" . $objectId;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response);
}

// Get current page from URL parameter
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$artworkIds = getArtworkIds($page, 12);  // Fetch 12 items per page
$artworks = [];

// Fetch details for each artwork
if ($artworkIds) {
    foreach ($artworkIds as $id) {
        $artwork = getArtworkDetails($id);
        if ($artwork && $artwork->primaryImage) {
            $artworks[] = $artwork;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metropolitan Museum Art Gallery</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .artwork {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px;
            transition: transform 0.2s;
        }
        .artwork:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .artwork img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 4px;
        }
        .artwork-info {
            padding: 15px 0;
        }
        .artwork-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .contact-button {
            background: #1a73e8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        .contact-button:hover {
            background: #1557b0;
        }
        .pagination {
            text-align: center;
            padding: 20px;
        }
        .pagination a {
            display: inline-block;
            padding: 8px 16px;
            background: #1a73e8;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 0 5px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            position: relative;
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .error-message {
            text-align: center;
            color: #d32f2f;
            padding: 20px;
            background: #ffebee;
            border-radius: 4px;
            margin: 20px;
        }
    </style>
</head>
<body>
    <h1>Metropolitan Museum Art Gallery</h1>
    
    <?php if (empty($artworks)): ?>
        <div class="error-message">
            <h2>Unable to load artworks</h2>
            <p>Please try refreshing the page. If the problem persists, check your internet connection.</p>
        </div>
    <?php else: ?>
        <div class="gallery">
            <?php foreach ($artworks as $artwork): ?>
                <div class="artwork">
                    <img src="<?php echo htmlspecialchars($artwork->primaryImage); ?>" 
                         alt="<?php echo htmlspecialchars($artwork->title); ?>">
                    <div class="artwork-info">
                        <h3><?php echo htmlspecialchars($artwork->title); ?></h3>
                        <p><strong>Artist:</strong> <?php echo htmlspecialchars($artwork->artistDisplayName ?: 'Unknown'); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($artwork->objectDate ?: 'Unknown'); ?></p>
                        <p><strong>Medium:</strong> <?php echo htmlspecialchars($artwork->medium ?: 'Not specified'); ?></p>
                        <button class="contact-button" 
                                onclick="showContactForm('<?php echo htmlspecialchars($artwork->objectID); ?>', '<?php echo htmlspecialchars($artwork->title); ?>')">
                            Inquire About This Artwork
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">Previous</a>
            <?php endif; ?>
            <a href="?page=<?php echo $page + 1; ?>">Next</a>
        </div>
    <?php endif; ?>

    <!-- Contact Form Modal -->
    <div id="contactModal" class="modal">
        <div class="modal-content">
            <h2>Contact About Artwork</h2>
            <form id="contactForm">
                <input type="hidden" id="artwork_id" name="artwork_id">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="contact-button">Send Inquiry</button>
                    <button type="button" class="contact-button" 
                            onclick="hideContactForm()" 
                            style="background: #666;">Close</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showContactForm(artworkId, artworkTitle) {
            document.getElementById('artwork_id').value = artworkId;
            document.getElementById('message').value = `I am interested in learning more about "${artworkTitle}".`;
            document.getElementById('contactModal').style.display = 'block';
        }

        function hideContactForm() {
            document.getElementById('contactModal').style.display = 'none';
        }

        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Here you would typically send the form data to your server
            alert('Thank you for your inquiry! We will contact you soon.');
            hideContactForm();
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('contactModal')) {
                hideContactForm();
            }
        }
    </script>
</body>
</html>