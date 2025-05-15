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

<div class="container">
    <div class="page-header">
        <h1>Book a Consultation</h1>
        <p>Choose from our network of professional visa consultants</p>
    </div>
    
    <!-- Search and Filter Controls -->
    <div class="search-filters">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <input type="text" id="search-consultant" class="form-control" placeholder="Search by name or specialization">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <select id="filter-rating" class="form-control">
                        <option value="">Filter by Rating</option>
                        <option value="4">4+ Stars</option>
                        <option value="3">3+ Stars</option>
                        <option value="2">2+ Stars</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <select id="filter-verified" class="form-control">
                        <option value="">All Consultants</option>
                        <option value="1">Verified by Visafy</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <button id="reset-filters" class="btn btn-secondary w-100">Reset</button>
            </div>
        </div>
    </div>
    
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
                    <div class="col-md-6 col-lg-4 mb-4 consultant-card-wrapper" 
                         data-name="<?php echo strtolower(htmlspecialchars($consultant['consultant_name'])); ?>"
                         data-specializations="<?php echo strtolower(htmlspecialchars($consultant['specializations'] ?? '')); ?>"
                         data-rating="<?php echo htmlspecialchars($consultant['average_rating']); ?>"
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
                                        <img src="<?php echo htmlspecialchars($consultant['profile_picture']); ?>" alt="<?php echo htmlspecialchars($consultant['consultant_name']); ?>">
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
.page-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px 0;
}

.page-header h1 {
    color: #042167;
    margin-bottom: 10px;
}

.page-header p {
    color: #666;
    font-size: 18px;
}

.search-filters {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
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

.btn {
    padding: 8px 16px;
    border-radius: 4px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.2s ease;
    font-size: 14px;
    border: none;
}

.btn-primary {
    background-color: #042167;
    color: white;
}

.btn-primary:hover {
    background-color: #031854;
}

.btn-secondary {
    background-color: #f0f0f0;
    color: #333;
}

.btn-secondary:hover {
    background-color: #e0e0e0;
}

@media (max-width: 768px) {
    .consultant-card-wrapper {
        margin-bottom: 20px;
    }
    
    .search-filters .row {
        gap: 10px;
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
            const verified = card.dataset.verified;
            
            // Check if card matches all filters
            const matchesSearch = searchTerm === '' || 
                                 name.includes(searchTerm) || 
                                 specializations.includes(searchTerm);
            
            const matchesRating = ratingValue === '' || rating >= parseFloat(ratingValue);
            
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
