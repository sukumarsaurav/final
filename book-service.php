<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/db_connect.php';


$page_title = "Book a Consultation";
require_once 'includes/header.php';

// Get all verified consultants with their profile details
$query = "SELECT 
    u.id AS consultant_id,
    CONCAT(u.first_name, ' ', u.last_name) AS consultant_name,
    u.email,
    u.phone,
    u.profile_picture,
    c.company_name,
    cp.bio,
    cp.specializations,
    cp.years_experience,
    cp.certifications,
    cp.languages,
    cp.is_featured,
    o.id AS organization_id,
    o.name AS organization_name,
    COALESCE(AVG(bf.rating), 0) AS average_rating,
    COUNT(DISTINCT bf.id) AS review_count,
    COUNT(DISTINCT vs.visa_service_id) AS services_count,
    cp.is_verified
FROM 
    users u
JOIN 
    consultants c ON u.id = c.user_id
JOIN 
    organizations o ON u.organization_id = o.id
LEFT JOIN 
    consultant_profiles cp ON u.id = cp.consultant_id
LEFT JOIN 
    visa_services vs ON u.id = vs.consultant_id AND vs.is_active = 1
LEFT JOIN 
    bookings b ON u.id = b.consultant_id
LEFT JOIN 
    booking_feedback bf ON b.id = bf.booking_id
WHERE 
    u.status = 'active' 
    AND u.deleted_at IS NULL
    AND u.user_type = 'consultant'
GROUP BY 
    u.id, u.first_name, u.last_name, u.email, u.phone, u.profile_picture,
    c.company_name, cp.bio, cp.specializations, cp.years_experience,
    cp.certifications, cp.languages, cp.is_featured, o.id, o.name, cp.is_verified
ORDER BY 
    cp.is_featured DESC, cp.is_verified DESC, average_rating DESC";

$result = $conn->query($query);
$consultants = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $consultants[] = $row;
    }
}
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">Book a Professional Consultation</h1>
            <p class="hero-subtitle">Connect with our network of experienced visa consultants to guide your immigration journey</p>
        </div>
        
        <!-- Search and Filter Controls (inline at bottom of hero) -->
        <div class="search-filters">
            <div class="filter-container">
                <div class="filter-item">
                    <input type="text" id="search-consultant" class="form-control" placeholder="Search by name or specialization">
                </div>
                <div class="filter-item">
                    <select id="filter-rating" class="form-control">
                        <option value="">All Ratings</option>
                        <option value="no-rating">No Ratings</option>
                        <option value="4">4+ Stars</option>
                        <option value="3">3+ Stars</option>
                        <option value="2">2+ Stars</option>
                    </select>
                </div>
                <div class="filter-item">
                    <select id="filter-verified" class="form-control">
                        <option value="">All Consultants</option>
                        <option value="1">Verified by Visafy</option>
                    </select>
                </div>
                <div class="filter-item">
                    <button id="reset-filters" class="btn btn-secondary">Reset</button>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <!-- Search and Filter Controls -->
    

    <!-- Consultants List -->
    <div class="consultants-list">
        <?php if (empty($consultants)): ?>
            <div class="empty-state">
                <i class="fas fa-user-tie"></i>
                <p>No consultants found. Please try different search criteria.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($consultants as $consultant): ?>
                    <div class="col-md-6 mb-4 consultant-card-wrapper" 
                         data-name="<?php echo strtolower(htmlspecialchars($consultant['consultant_name'])); ?>"
                         data-specializations="<?php echo strtolower(htmlspecialchars($consultant['specializations'] ?? '')); ?>"
                         data-rating="<?php echo htmlspecialchars($consultant['average_rating']); ?>"
                         data-has-rating="<?php echo $consultant['review_count'] > 0 ? '1' : '0'; ?>"
                         data-verified="<?php echo !empty($consultant['is_verified']) ? '1' : '0'; ?>">
                        
                        <div class="consultant-card">
                            <?php if (!empty($consultant['is_verified'])): ?>
                                <div class="verified-badge">
                                    <i class="fas fa-check-circle"></i> Verified by Visafy
                                </div>
                            <?php endif; ?>
                            
                            <div class="consultant-header">
                                <div class="consultant-img">
                                    <?php if (!empty($consultant['profile_picture'])): ?>
                                        <?php 
                                        // Fix profile picture path - add 'uploads/' if not present
                                        $profile_picture = $consultant['profile_picture'];
                                        if (strpos($profile_picture, 'users/') === 0) {
                                            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $profile_picture)) {
                                                $profile_img = '/uploads/' . $profile_picture;
                                            }
                                        // } else {
                                        //     // Legacy structure
                                        //     if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/profiles/' . $profile_picture)) {
                                        //         $profile_img = '/uploads/profiles/' . $profile_picture;
                                        //     } else if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/profile/' . $profile_picture)) {
                                        //         $profile_img = '/uploads/profile/' . $profile_picture;
                                        //     }
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="<?php echo htmlspecialchars($consultant['consultant_name']); ?>">
                                    <?php else: ?>
                                        <div class="default-avatar">
                                            <?php echo strtoupper(substr($consultant['consultant_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="consultant-info">
                                    <h3><?php echo htmlspecialchars($consultant['consultant_name']); ?></h3>
                                    <p class="company-name"><?php echo htmlspecialchars($consultant['company_name']); ?></p>
                                    <div class="rating">
                                        <?php
                                        $rating = round($consultant['average_rating'] * 2) / 2; // Round to nearest 0.5
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($rating >= $i):
                                                echo '<i class="fas fa-star"></i>';
                                            elseif ($rating >= $i - 0.5):
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            else:
                                                echo '<i class="far fa-star"></i>';
                                            endif;
                                        endfor;
                                        ?>
                                        <span>(<?php echo $consultant['review_count']; ?> reviews)</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="consultant-body">
                                <?php if (!empty($consultant['bio'])): ?>
                                    <p class="bio"><?php echo htmlspecialchars(substr($consultant['bio'], 0, 150)) . (strlen($consultant['bio']) > 150 ? '...' : ''); ?></p>
                                <?php else: ?>
                                    <p class="bio">No bio information available.</p>
                                <?php endif; ?>
                                
                                <div class="specializations">
                                    <strong>Specializations:</strong>
                                    <?php if (!empty($consultant['specializations'])): ?>
                                        <p><?php echo htmlspecialchars($consultant['specializations']); ?></p>
                                    <?php else: ?>
                                        <p>General visa services</p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="meta-info">
                                    <div class="meta-item">
                                        <i class="fas fa-briefcase"></i>
                                        <span><?php echo !empty($consultant['years_experience']) ? $consultant['years_experience'] . '+ years exp.' : 'Experience not specified'; ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-globe"></i>
                                        <span><?php echo !empty($consultant['languages']) ? htmlspecialchars($consultant['languages']) : 'Languages not specified'; ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-passport"></i>
                                        <span><?php echo $consultant['services_count']; ?> Services</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="consultant-footer">
                                <a href="consultant-profile.php?id=<?php echo $consultant['consultant_id']; ?>" class="btn btn-secondary">View Profile</a>
                                <a href="book-consultation.php?consultant_id=<?php echo $consultant['consultant_id']; ?>" class="btn btn-primary">Book Consultation</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- CSS -->
<style>
:root {
    --primary-color: #eaaa34;
    --primary-light: rgba(234, 170, 52, 0.1);
    --primary-medium: rgba(234, 170, 52, 0.2);
    --dark-blue: #042167;
    --text-color: #333;
    --text-light: #666;
    --background-light: #f8f9fa;
    --white: #fff;
    --border-color: #e5e7eb;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --border-radius: 0.5rem;
    --border-radius-lg: 1rem;
    --transition: all 0.3s ease;
}

/* Hero Section Styles */
.hero {
    padding: 80px 0;
    background-color: rgba(234, 170, 52, 0.05);
    color: var(--color-light);
    overflow: hidden;
    position: relative;
}

.hero-content {
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
}

.hero-title {
    font-size: 3.5rem;
    color: #042167;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.2;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.hero-subtitle {
    font-size: 1.2rem;
    margin-bottom: 30px;
    line-height: 1.6;
    opacity: 0.9;
    color: #042167;
}

/* Search Filters */
.search-filters {
    background-color: var(--white);
    padding: 20px;
    border-radius: var(--border-radius);
    margin-bottom: 30px;
    box-shadow: var(--shadow);
}

.filter-container {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
    justify-content: space-between;
}

.filter-item {
    flex: 1;
    min-width: 200px;
}

.filter-item:last-child {
    flex: 0 0 auto;
    min-width: 100px;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 14px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px var(--primary-light);
}

.btn {
    padding: 12px 25px;
    border-radius: var(--border-radius);
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    display: inline-block;
    cursor: pointer;
    border: none;
    width: 100%;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--dark-blue);
    transform: translateY(-2px);
}

.btn-secondary {
    background-color: var(--white);
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background-color: var(--background-light);
    transform: translateY(-2px);
}

.empty-state {
    text-align: center;
    padding: 50px 0;
    color: #666;
}

.empty-state i {
    font-size: 48px;
    color: #ccc;
    margin-bottom: 15px;
}

.consultant-card {
    background-color: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    position: relative;
}

.consultant-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

.verified-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: #28a745;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 5px;
    z-index: 1;
}

.consultant-header {
    padding: 20px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #eee;
}

.consultant-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 15px;
    flex-shrink: 0;
    background-color: #f0f0f0;
    border: 1px solid #ddd;
}

.consultant-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.default-avatar {
    width: 100%;
    height: 100%;
    background-color: #042167;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    font-weight: bold;
}

.consultant-info h3 {
    margin: 0 0 5px 0;
    color: #042167;
    font-size: 18px;
}

.company-name {
    margin: 0 0 5px 0;
    color: #666;
    font-size: 14px;
}

.rating {
    color: #f8bb00;
    font-size: 14px;
}

.rating span {
    color: #999;
    margin-left: 5px;
}

.consultant-body {
    padding: 20px;
    flex-grow: 1;
}

.bio {
    margin-top: 0;
    color: #555;
    line-height: 1.4;
}

.specializations {
    margin-top: 15px;
}

.specializations strong {
    color: #042167;
}

.meta-info {
    margin-top: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.meta-item {
    display: flex;
    align-items: center;
    font-size: 13px;
    color: #666;
}

.meta-item i {
    margin-right: 5px;
    color: #042167;
}

.consultant-footer {
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
}

.consultant-footer .btn {
    width: 48%;
    padding: 12px 25px;
    border-radius: var(--border-radius);
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    display: inline-block;
    cursor: pointer;
    border: none;
}

/* Update grid layout */
.consultants-list .row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    margin: 0;
}

.consultant-card-wrapper {
    width: 100%;
    padding: 0;
}

/* Responsive adjustments */
@media (max-width: 991px) {
    .consultants-list .row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .consultant-card-wrapper {
        margin-bottom: 20px;
    }
    
    .meta-info {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search and filter functionality
    const searchInput = document.getElementById('search-consultant');
    const ratingFilter = document.getElementById('filter-rating');
    const verifiedFilter = document.getElementById('filter-verified');
    const resetButton = document.getElementById('reset-filters');
    const consultantCards = document.querySelectorAll('.consultant-card-wrapper');
    
    // Function to filter consultants
    function filterConsultants() {
        const searchTerm = searchInput.value.toLowerCase();
        const ratingValue = ratingFilter.value;
        const verifiedValue = verifiedFilter.value;
        
        consultantCards.forEach(card => {
            const name = card.dataset.name;
            const specializations = card.dataset.specializations;
            const rating = parseFloat(card.dataset.rating);
            const hasRating = card.dataset.hasRating === '1';
            const verified = card.dataset.verified;
            
            // Check if card matches all filters
            const matchesSearch = searchTerm === '' || 
                                 name.includes(searchTerm) || 
                                 specializations.includes(searchTerm);
            
            let matchesRating = true;
            if (ratingValue !== '') {
                if (ratingValue === 'no-rating') {
                    matchesRating = !hasRating;
                } else {
                    matchesRating = hasRating && rating >= parseFloat(ratingValue);
                }
            }
            
            const matchesVerified = verifiedValue === '' || 
                                   (verifiedValue === '1' && verified === '1');
            
            // Show or hide card based on filter results
            if (matchesSearch && matchesRating && matchesVerified) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Check if any cards are visible
        const visibleCards = document.querySelectorAll('.consultant-card-wrapper[style="display: block"]');
        const emptyState = document.querySelector('.empty-state');
        
        if (visibleCards.length === 0 && !emptyState) {
            const consultantsList = document.querySelector('.consultants-list .row');
            if (consultantsList) {
                consultantsList.innerHTML += `
                    <div class="empty-state">
                        <i class="fas fa-user-tie"></i>
                        <p>No consultants found. Please try different search criteria.</p>
                    </div>
                `;
            }
        } else if (visibleCards.length > 0) {
            const emptyState = document.querySelector('.empty-state');
            if (emptyState) {
                emptyState.remove();
            }
        }
    }
    
    // Add event listeners
    searchInput.addEventListener('input', filterConsultants);
    ratingFilter.addEventListener('change', filterConsultants);
    verifiedFilter.addEventListener('change', filterConsultants);
    
    // Reset filters
    resetButton.addEventListener('click', function() {
        searchInput.value = '';
        ratingFilter.value = '';
        verifiedFilter.value = '';
        
        consultantCards.forEach(card => {
            card.style.display = 'block';
        });
        
        const emptyState = document.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
