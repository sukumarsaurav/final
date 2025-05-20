<?php
// Set page title
$page_title = "Eligibility Calculator Management";

// Include header
include('includes/header.php');

// Fetch basic stats
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM decision_tree_questions");
$stmt->execute();
$questions_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_assessments");
$stmt->execute();
$assessments_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_assessments WHERE is_complete = 1");
$stmt->execute();
$completed_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Get categories
$stmt = $conn->prepare("SELECT id, name, description FROM decision_tree_categories ORDER BY name");
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-sitemap fa-3x text-primary"></i>
                        </div>
                        <div>
                            <h4 class="mb-1">Eligibility Checker Management</h4>
                            <p class="text-muted mb-0">Create and manage decision trees for eligibility checking.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Stats Cards -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card stats-card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Questions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $questions_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-question-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card stats-card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Assessments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $assessments_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card stats-card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Completed Assessments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-double fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Manage Questions</h6>
                </div>
                <div class="card-body">
                    <p>Create and manage questions in your decision tree.</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="add-question.php" class="btn btn-primary btn-block action-btn">
                                <i class="fas fa-plus"></i> Add New Question
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="manage-questions.php" class="btn btn-info btn-block action-btn">
                                <i class="fas fa-list"></i> View All Questions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Assessment Results</h6>
                </div>
                <div class="card-body">
                    <p>View and analyze user assessment results.</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="assessment-results.php" class="btn btn-success btn-block action-btn">
                                <i class="fas fa-chart-pie"></i> View Results
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="export-assessments.php" class="btn btn-secondary btn-block action-btn">
                                <i class="fas fa-file-export"></i> Export Data
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Categories -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Question Categories</h6>
                    <a href="manage-categories.php" class="btn btn-sm btn-primary">Manage</a>
                </div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <p class="text-center">No categories defined yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Questions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): 
                                        // Get question count for this category
                                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM decision_tree_questions WHERE category_id = ?");
                                        $stmt->bind_param("i", $category['id']);
                                        $stmt->execute();
                                        $question_count = $stmt->get_result()->fetch_assoc()['count'];
                                        $stmt->close();
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo htmlspecialchars($category['description'] ?? 'No description'); ?></td>
                                            <td><?php echo $question_count; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Decision Tree Visualization -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Decision Tree Visualization</h6>
                </div>
                <div class="card-body">
                    <div id="decision-tree-container" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vis.js CSS and JavaScript -->
<link href="https://unpkg.com/vis-network/dist/dist/vis-network.min.css" rel="stylesheet" type="text/css" />
<script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch tree data for visualization
    fetch('api/get-decision-tree.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('decision-tree-container');
            
            if (!data || !data.nodes || data.nodes.length === 0) {
                container.innerHTML = '<div class="text-center py-5"><p>No decision tree data available.</p><a href="add-question.php" class="btn btn-primary">Create Your First Question</a></div>';
                return;
            }
            
            // Create a network
            const network = new vis.Network(container, data, {
                layout: {
                    hierarchical: {
                        direction: 'UD',
                        sortMethod: 'directed',
                        levelSeparation: 100,
                        nodeSpacing: 150
                    }
                },
                nodes: {
                    shape: 'box',
                    margin: 10,
                    widthConstraint: {
                        maximum: 200
                    }
                },
                edges: {
                    arrows: 'to',
                    smooth: {
                        type: 'cubicBezier',
                        forceDirection: 'vertical',
                        roundness: 0.4
                    }
                },
                physics: {
                    enabled: false
                }
            });
            
            // Add click event to nodes
            network.on('click', function(params) {
                if (params.nodes.length > 0) {
                    const nodeId = params.nodes[0];
                    window.location.href = `edit-question.php?id=${nodeId}`;
                }
            });
        })
        .catch(error => {
            console.error('Error fetching decision tree data:', error);
            document.getElementById('decision-tree-container').innerHTML = '<p class="text-center text-danger">Error loading decision tree visualization.</p>';
        });
});
</script>

<?php
// Include footer
include('includes/footer.php');
?> 