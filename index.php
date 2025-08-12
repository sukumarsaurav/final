<?php
// Include session management
require_once "includes/session.php";

$page_title = "Visafy | Canadian Immigration Consultancy";
include('includes/header.php');
?>
<link rel="stylesheet" href="assets/css/responsive.css">

<!-- Hero Section -->
<section class="hero bg-white">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">Visafy, Your AI-fied Immigration Partner</h1>
            <p class="hero-subtitle">An Immigration Marketplace serving both Immigration Professionals & Visa seekers across the globe</p>
            <div class="hero-buttons">
                <a href="eligibility-test.php" class="btn btn-primary">Check Eligibility</a>
                <a href="book-service.php" class="btn btn-secondary">Get Consultation</a>
                </div>
            </div>
            <div class="hero-image-container">
                <img src="assets/images/main-consultant.png" alt="Main Consultant" class="hero-image">
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="section services bg-dark-blue">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Our Platform</h2>
        <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Visafy is world's first Immigration Marketplace connecting Immigration Professionals and Applicants in one unified platform. For Immigration Professionals, it is a AI powered CRM Platform. For Applicants, it helps you at every stage of your Visa & Immigration journey with access to free Eligibility Checks, DIY tools & access to Professional assistance.</p>
        
        <div class="services-grid" style="grid-template-columns: 4.5fr 2fr 2fr; gap: 2rem;">
             <!-- Platform Image Column -->
             <div class="platform-image-column" data-aos="fade-up" data-aos-delay="400">
                <div class="animated-platform-image">
                    <img src="assets/images/main-consultant.png" alt="Visafy Platform" style="max-width: 100%; height: auto;">
                </div>
            </div>
            <!-- For Applicants -->
            <div class="service-card" data-aos="fade-up" data-aos-delay="200" style="min-height: 500px; display: flex; flex-direction: column; justify-content: space-between;">
              
                <h3 style="text-align: center; margin-bottom: 2rem; font-size: 1.8rem;">Applicants</h3>
                <div class="service-features" style="flex-grow: 1;">
                    <div class="feature-item" style="margin-bottom: 1.5rem;">
                        <span>Check Eligibility</span>
                    </div>
                    <div class="feature-item" style="margin-bottom: 1.5rem;">
                        <span>Get Professional Assistance</span>
                    </div>
                    <div class="feature-item" style="margin-bottom: 1.5rem;">
                        <span>Engage with Immigration Professionals</span>
                    </div>
                    <div class="feature-item" style="margin-bottom: 1.5rem;">
                        <span>Browse thru reviews & ratings</span>
                    </div>
                    <div class="feature-item" style="margin-bottom: 1.5rem;">
                        <span>Manage documents securely</span>
                    </div>
                </div>
                <a href="register.php?type=applicant" class="btn btn-primary" style="margin-top: auto;">Get Started</a>
            </div>

            <!-- For Consultants -->
            <div class="service-card" data-aos="fade-up" data-aos-delay="300" style="min-height: 500px; display: flex; flex-direction: column; justify-content: space-between;">
              
                <h3 style="text-align: center; margin-bottom: 2rem; font-size: 1.8rem;">Immigration Professionals</h3>
                <div class="service-features" style="flex-grow: 1;">
                    <div class="feature-item" style="margin-bottom: 1.5rem;">
                        <span>Full-fledged Practice Management system</span>
                    </div>
                    <div class="feature-item" style="margin-bottom: 1.5rem;">
                        <span>Grow your reputation with verified reviews</span>
                    </div>
                    <div class="feature-item" style="margin-bottom: 1.5rem;">
                        <span>Manage Clients, Cases, Staff in one place</span>
                    </div>
                    <div class="feature-item" style="margin-bottom: 1.5rem;">
                        <span>AI Powered Case Law & Compliance</span>
                    </div>
                    <div class="feature-item" style="margin-bottom: 1.5rem;">
                        <span>Stay Regulator Compliant & reduce Practice risks</span>
                    </div>
                </div>
                <a href="become-member.php" class="btn btn-primary" style="margin-top: auto;">Join as Consultant</a>
            </div>
            
           
        </div>
    </div>
</section>

<style>
.animated-platform-image {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    transition: all 0.5s ease;
}

.animated-platform-image::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(to right, transparent, rgba(255,255,255,0.2), transparent);
    transform: skewX(-25deg);
    transition: all 0.75s ease;
    z-index: 1;
}

.animated-platform-image:hover::before {
    left: 100%;
}

.animated-platform-image img {
    transform: scale(1);
    transition: transform 0.6s ease-in-out;
}

.animated-platform-image:hover img {
    transform: scale(1.05);
}

.platform-image-column {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}
</style>

<!-- Platform Benefits Section -->
<section class="section platform-benefits bg-white">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Your Immigration Journey, Simplified</h2>
        <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Experience a seamless immigration process
            with our all-in-one digital platform</p>

        <div class="immigration-benefits-container">
            <!-- Dashboard Section -->
            <div class="immigration-benefit-section" data-aos="fade-up">
                <div class="immigration-benefit-content">
                    <div class="immigration-benefit-info">
                        <h3>
                            Real-Time Application Tracking
                        </h3>
                        <p class="immigration-benefit-description">
                            Monitor your applications in real-time and stay updated on every milestone of your
                            immigration journey.
                        </p>
                        <ul class="immigration-benefit-features">
                            <li>
                                <div class="immigration-check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Live Status Updates</strong>
                                    <p>Monitor your application progress in real-time with instant notifications on
                                        status changes</p>
                                </div>
                            </li>
                            <li>
                                <div class="immigration-check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Timeline Visualization</strong>
                                    <p>View your entire application journey with clear milestones and expected
                                        completion dates</p>
                                </div>
                            </li>
                            <li>
                                <div class="immigration-check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Personalized To-Do Lists</strong>
                                    <p>Stay on track with custom checklists and timely reminders for required actions
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="immigration-benefit-image">
                        <!-- Decorative Circles -->
                        <div class="immigration-circle-decoration">
                            <div class="immigration-circle immigration-circle-1"></div>
                            <div class="immigration-circle immigration-circle-2"></div>
                        </div>
                        <!-- SVG Shape Background -->
                        <div class="immigration-svg-background">
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="immigration-shape immigration-shape-1">
                                <path d="M42.7,-73.4C55.9,-67.1,67.7,-57.2,75.9,-44.6C84.1,-32,88.7,-16,88.1,-0.3C87.5,15.3,81.8,30.6,73.1,43.9C64.4,57.2,52.8,68.5,39.1,75.3C25.4,82.1,9.7,84.4,-5.9,83.1C-21.5,81.8,-37,76.9,-50.9,68.5C-64.8,60.1,-77.1,48.3,-83.3,33.8C-89.5,19.3,-89.6,2.2,-85.1,-13.2C-80.6,-28.6,-71.5,-42.3,-59.8,-51.6C-48.1,-60.9,-33.8,-65.8,-20.4,-70.3C-7,-74.8,5.5,-78.9,18.8,-79.1C32.1,-79.3,46.2,-75.6,42.7,-73.4Z" transform="translate(100 100)" />
                            </svg>
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="immigration-shape immigration-shape-2">
                                <path d="M47.7,-80.4C59.3,-71.3,64.8,-54.7,71.9,-39.4C79,-24.2,87.7,-10.3,87.5,3.4C87.3,17.1,78.1,30.6,68.3,42.8C58.5,55,48,65.9,35.1,73.4C22.2,80.9,6.9,85,-8.9,84.5C-24.8,84,-41.2,78.9,-54.3,69.5C-67.4,60.1,-77.2,46.4,-83.1,30.8C-89.5,19.3,-91,-1.1,-87.4,-16.2C-83.8,-31.3,-74.6,-45.2,-62.3,-54.8C-50,-64.4,-34.6,-69.8,-19.9,-74.9C-5.2,-80,9.7,-84.8,24.4,-84.1C39.2,-83.4,53.8,-77.2,47.7,-80.4Z" transform="translate(100 100)" />
                            </svg>
                        </div>

                        <div class="immigration-image-container">
                            <img src="assets/images/visafy-dashboard.png" alt="Application Dashboard">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Section -->
            <div class="immigration-benefit-section" data-aos="fade-up">
                <div class="immigration-benefit-content reverse">
                    <div class="immigration-benefit-image">
                   
                        <!-- SVG Shape Background -->
                        <div class="immigration-svg-background">
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="immigration-shape immigration-shape-3">
                                <path
                                    d="M39.9,-68.1C52.6,-62.1,64.5,-53.1,72.7,-41C80.9,-28.8,85.4,-14.4,83.9,-0.9C82.3,12.7,74.8,25.4,66.4,37.8C58,50.3,48.7,62.5,36.5,70.1C24.2,77.7,9.1,80.7,-5.9,79.5C-20.9,78.3,-35.9,72.9,-47.5,64C-59.1,55,-67.3,42.5,-73.4,28.5C-79.5,14.5,-83.5,-1,-80.8,-15.2C-78.1,-29.4,-68.7,-42.3,-56.8,-48.9C-44.9,-55.5,-30.5,-55.8,-17.7,-61.8C-4.9,-67.8,6.3,-79.5,18.4,-80.5C30.5,-81.5,43.5,-71.8,39.9,-68.1Z"
                                    transform="translate(100 100)" />
                            </svg>
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="immigration-shape immigration-shape-4">
                                <path
                                    d="M47.3,-79.7C62.9,-71.9,78.5,-62.3,86.4,-48.3C94.3,-34.3,94.5,-15.7,90.3,0.9C86.1,17.4,77.5,31.8,67.2,44.7C56.9,57.6,44.9,69,30.7,76.2C16.5,83.4,0.1,86.4,-16.4,83.3C-32.9,80.2,-45.5,71,-57.8,59C-70.1,47,-80.1,32.2,-84.6,15.6C-89.1,-1,-88.1,-19.4,-81.5,-35.1C-74.9,-50.8,-62.7,-63.8,-48.1,-72.1C-33.5,-80.4,-16.7,-84,0.2,-84.4C17.2,-84.8,34.3,-82,47.3,-79.7Z"
                                    transform="translate(100 100)" />
                            </svg>
                        </div>

                        <div class="immigration-image-container">
                            <img src="assets/images/documents.png" alt="Document Management">
                        </div>
                    </div>
                    <div class="immigration-benefit-info">
                        <h3>
                            Streamlined Document Handling
                        </h3>
                        <p class="immigration-benefit-description">
                            Securely manage all your important documents with our specialized document handling system.
                        </p>
                        <ul class="immigration-benefit-features">
                            <li>
                                <div class="immigration-check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Secure Document Hub</strong>
                                    <p>Upload, store, and share your important documents in a highly secured environment
                                    </p>
                                </div>
                            </li>
                            <li>
                                <div class="immigration-check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Document Checklists</strong>
                                    <p>Access personalized checklists of required documents based on your visa category
                                    </p>
                                </div>
                            </li>
                            <li>
                                <div class="immigration-check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Document Status Tracking</strong>
                                    <p>Monitor which documents are approved, pending or require revision with visual
                                        indicators</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Messaging Section -->
            <div class="immigration-benefit-section" data-aos="fade-up">
                <div class="immigration-benefit-content">
                    <div class="immigration-benefit-info">
                        <h3>
                            Direct Expert Communication
                        </h3>
                        <p class="immigration-benefit-description">
                            Get instant support and guidance from immigration experts through our integrated messaging
                            system.
                        </p>
                        <ul class="immigration-benefit-features">
                            <li>
                                <div class="immigration-check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Instant Messaging</strong>
                                    <p>Connect directly with your assigned immigration consultant for quick questions
                                        and updates</p>
                                </div>
                            </li>
                            <li>
                                <div class="immigration-check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Conversation History</strong>
                                    <p>Access your complete communication history for reference and documentation</p>
                                </div>
                            </li>
                            <li>
                                <div class="immigration-check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Multilingual Support</strong>
                                    <p>Communicate with team members in multiple languages with real-time message
                                        notifications</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="immigration-benefit-image">
                        <!-- Decorative Circles -->
                        <div class="immigration-circle-decoration">
                            <div class="immigration-circle immigration-circle-1"></div>
                            <div class="immigration-circle immigration-circle-2"></div>
                        </div>
                        <!-- SVG Shape Background -->
                        <div class="immigration-svg-background">
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="immigration-shape immigration-shape-5">
                                <path
                                    d="M48.2,-76.1C63.3,-69.2,77.2,-58.4,84.6,-44.2C92,-30,92.8,-12.5,89.6,3.7C86.3,19.9,78.9,34.8,68.9,47.9C58.9,61,46.2,72.3,31.5,77.8C16.8,83.2,0.1,82.8,-16.4,79.7C-32.9,76.6,-49.2,70.8,-62.7,60.3C-76.2,49.8,-87,34.6,-90.9,17.8C-94.8,0.9,-91.9,-17.5,-84.2,-32.8C-76.5,-48.1,-64,-60.2,-49.5,-67.5C-35,-74.8,-18.5,-77.3,-1.2,-75.5C16.1,-73.7,33.1,-83,48.2,-76.1Z"
                                    transform="translate(100 100)" />
                            </svg>
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="immigration-shape immigration-shape-6">
                                <path
                                    d="M45.3,-76.3C59.9,-69.1,73.8,-59.3,82.1,-45.9C90.4,-32.5,93.2,-15.3,90.6,0.8C88,16.8,80,31.7,70.1,45.1C60.2,58.6,48.4,70.7,34.4,77.4C20.4,84.1,4.3,85.4,-12.4,83.5C-29.1,81.6,-46.5,76.4,-59.8,66.1C-73.1,55.7,-82.3,40.1,-86.9,23.4C-91.5,6.7,-91.5,-11.2,-85.8,-26.5C-80.1,-41.8,-68.7,-54.5,-54.9,-61.9C-41.1,-69.3,-24.9,-71.3,-8.9,-70.1C7.1,-68.9,14.1,-64.5,26.3,-67.2C38.5,-69.9,55.8,-79.7,45.3,-76.3Z"
                                    transform="translate(100 100)" />
                            </svg>
                        </div>

                        <div class="immigration-image-container">
                            <img src="assets/images/messaging.png" alt="Messaging System">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->

        </div>
    </div>
</section>


<!-- Key Benefits Section -->
<section class="section key-benefits-section bg-dark-blue">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Why Choose Visafy?</h2>
        <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Experience a seamless immigration process
            with our comprehensive platform</p>
        
        <div class="key-benefits-grid">
            <!-- Row 1: Licensed Consultants & Digital Platform -->
            <div class="key-benefit-card" data-aos="fade-up" data-aos-delay="200">
                <div class="key-benefit-image">
                    <img src="assets/images/main-consultant.png" alt="Licensed Consultants">
                </div>
                <div class="key-benefit-content">
                    <h3>Licensed Consultants</h3>
                    <p>Get expert guidance from ICCRC licensed consultants with proven track records in successful
                        applications.</p>
                </div>
            </div>

            <div class="key-benefit-card" data-aos="fade-up" data-aos-delay="300">
                <div class="key-benefit-image">
                    <img src="assets/images/main-consultant.png" alt="Digital Platform">
                </div>
                <div class="key-benefit-content">
                    <h3>Digital-First Platform</h3>
                    <p>Manage your entire immigration journey through our user-friendly digital platform, accessible
                        anytime, anywhere.</p>
                </div>
            </div>

            <!-- Row 2: Document Management & Real-time Updates -->
            <div class="key-benefit-card" data-aos="fade-up" data-aos-delay="400">
                <div class="key-benefit-image">
                    <img src="assets/images/main-consultant.png" alt="Document Management">
                </div>
                <div class="key-benefit-content">
                    <h3>Smart Document Management</h3>
                    <p>Securely store and manage all your documents with our advanced document handling system.</p>
                </div>
            </div>

            <div class="key-benefit-card" data-aos="fade-up" data-aos-delay="500">
                <div class="key-benefit-image">
                    <img src="assets/images/main-consultant.png" alt="Real-time Updates">
                </div>
                <div class="key-benefit-content">
                    <h3>Real-time Updates</h3>
                    <p>Stay informed with instant notifications and real-time updates on your application status.</p>
                </div>
            </div>

            <!-- Row 3: High Success Rate & 24/7 Support -->
            <div class="key-benefit-card" data-aos="fade-up" data-aos-delay="600">
                <div class="key-benefit-image">
                    <img src="assets/images/main-consultant.png" alt="High Success Rate">
                </div>
                <div class="key-benefit-content">
                    <h3>High Success Rate</h3>
                    <p>Benefit from our proven track records of successful applications and satisfied clients.</p>
                </div>
            </div>

            <div class="key-benefit-card" data-aos="fade-up" data-aos-delay="700">
                <div class="key-benefit-image">
                    <img src="assets/images/main-consultant.png" alt="24/7 Support">
                </div>
                <div class="key-benefit-content">
                    <h3>24/7 Support</h3>
                    <p>Get assistance whenever you need it with our round-the-clock customer support team.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">What Our Clients Say</h2>
        <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Hear from people who have successfully navigated their immigration journey with us</p>
        
        <div class="testimonials-container">
            <div class="testimonial-arrow prev" id="prevTestimonial">
                <i class="fas fa-chevron-left"></i>
            </div>
            
            <div class="testimonials-slider" id="testimonialsSlider">
                <!-- Testimonial 1 -->
                <div class="testimonial-card" data-aos="fade-up">
                    <img src="assets/images/default-profile.svg" alt="Sarah Johnson" class="testimonial-avatar">
                    <p class="testimonial-quote">Visafy made my immigration process so much easier than I expected. Their platform kept me updated at every step, and my consultant was incredibly knowledgeable and supportive throughout the journey.</p>
                    <h4 class="testimonial-author">Sarah Johnson</h4>
                    <p class="testimonial-role">Successful Express Entry Applicant</p>
                </div>
                
                <!-- Testimonial 2 -->
                <div class="testimonial-card" data-aos="fade-up">
                    <img src="assets/images/default-profile.svg" alt="Michael Chen" class="testimonial-avatar">
                    <p class="testimonial-quote">As someone who was overwhelmed by the immigration process, Visafy was a game-changer. The eligibility assessment was spot-on, and the document management system saved me countless hours of work.</p>
                    <h4 class="testimonial-author">Michael Chen</h4>
                    <p class="testimonial-role">Work Permit Holder</p>
                </div>
                
                <!-- Testimonial 3 -->
                <div class="testimonial-card" data-aos="fade-up">
                    <img src="assets/images/default-profile.svg" alt="Priya Sharma" class="testimonial-avatar">
                    <p class="testimonial-quote">I can't thank Visafy enough for their exceptional service. Their platform connected me with an amazing consultant who guided me through every step of my family sponsorship application. Highly recommended!</p>
                    <h4 class="testimonial-author">Priya Sharma</h4>
                    <p class="testimonial-role">Family Sponsorship Applicant</p>
                </div>
            </div>
            
            <div class="testimonial-arrow next" id="nextTestimonial">
                <i class="fas fa-chevron-right"></i>
            </div>
            
            <div class="testimonial-controls">
                <div class="testimonial-dot active" data-index="0"></div>
                <div class="testimonial-dot" data-index="1"></div>
                <div class="testimonial-dot" data-index="2"></div>
            </div>
        </div>
    </div>
</section>

<!-- Steps Section -->
<section class="section steps bg-white">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Your Global Immigration Journey</h2>
        <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Follow these simple steps to start your
            immigration process anywhere in the world</p>
        
       

        <div class="steps-container">
            <!-- Connecting Line -->
            <div class="steps-connecting-line"></div>
            
            <!-- Step 1 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="200">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Create Your Account</h3>
                    <p>Begin your immigration journey by creating a free account. Complete your profile with essential
                        information to help us understand your goals and requirements.</p>
                    <a href="register.php" class="btn">Sign Up Now</a>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="300">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Check Eligibility</h3>
                    <p>Take our comprehensive eligibility assessment to determine your immigration options. Our advanced
                        algorithm analyzes your profile against various immigration programs.</p>
                    <a href="eligibility-test.php" class="btn">Start Assessment</a>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="400">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Book Consultation</h3>
                    <p>Schedule a free consultation with our immigration experts. Discuss your options, get personalized
                        advice, and learn about the next steps in your immigration journey.</p>
                    <a href="book-consultation.php" class="btn">Book Now</a>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="500">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3>Begin Your Application</h3>
                    <p>Start your immigration process with expert guidance. Our consultants will help you prepare and
                        submit your application, ensuring all requirements are met.</p>
                    <a href="services.php" class="btn">View Services</a>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="benefits-cta" data-aos="fade-up">
    <div class="cta-content">
        <div class="cta-text">
            <p>Ready to experience our innovative platform?</p>
            <div class="cta-buttons">
                <a href="register.php" class="cta-btn-primary">
                    <i class="fas fa-user-plus"></i> Sign up for Free
                </a>
                <a href="how-it-works.php" class="cta-btn-secondary">
                    <i class="fas fa-play-circle"></i> Watch Demo
                </a>
            </div>
        </div>
        <div class="cta-image">
            <img src="assets/images/cta-whitebg.png" alt="Call to Action">
        </div>
    </div>
</div>
<!-- FAQ Section -->
<section class="faq-section bg-white">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Frequently Asked Questions</h2>
        <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Find answers to common questions about our immigration services and platform</p>
        
        <div class="faq-container">
            <div class="faq-column">
                <div class="faq-item" data-aos="fade-up" data-aos-delay="200">
                    <div class="faq-question">
                        <h3>How does Visafy's eligibility assessment work?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Our AI-powered eligibility assessment analyzes your profile against various immigration programs, including Express Entry, Provincial Nominee Programs, and other pathways. The assessment considers factors like age, education, work experience, language proficiency, and other criteria to provide personalized recommendations.</p>
                    </div>
                </div>
                
                <div class="faq-item" data-aos="fade-up" data-aos-delay="300">
                    <div class="faq-question">
                        <h3>Are all consultants on Visafy licensed and verified?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, all immigration consultants on our platform are verified and licensed by the Immigration Consultants of Canada Regulatory Council (ICCRC). We conduct thorough background checks and verify credentials before allowing consultants to join our platform.</p>
                    </div>
                </div>
                
                <div class="faq-item" data-aos="fade-up" data-aos-delay="400">
                    <div class="faq-question">
                        <h3>How much does it cost to use Visafy's services?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Our eligibility assessment is completely free. Consultation fees vary depending on the consultant and service type, but you can view all pricing upfront before booking. We also offer transparent pricing with no hidden fees.</p>
                    </div>
                </div>
            </div>
            
            <div class="faq-column">
                <div class="faq-item" data-aos="fade-up" data-aos-delay="500">
                    <div class="faq-question">
                        <h3>Can I track my application progress on Visafy?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Absolutely! Our platform provides real-time application tracking with status updates, milestone notifications, and document management. You'll receive instant notifications when your application status changes or requires action.</p>
                    </div>
                </div>
                
                <div class="faq-item" data-aos="fade-up" data-aos-delay="600">
                    <div class="faq-question">
                        <h3>What types of immigration services does Visafy offer?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We offer comprehensive immigration services including Express Entry applications, Provincial Nominee Programs, family sponsorship, work permits, study permits, visitor visas, and citizenship applications. Our platform supports all major Canadian immigration pathways.</p>
                    </div>
                </div>
                
                <div class="faq-item" data-aos="fade-up" data-aos-delay="700">
                    <div class="faq-question">
                        <h3>How secure is my personal information on Visafy?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We prioritize the security of your personal information. Our platform uses enterprise-grade encryption, secure document storage, and complies with Canadian privacy laws. Your data is protected with the highest security standards and is never shared without your consent.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Testimonial slider functionality
    const slider = document.getElementById('testimonialsSlider');
    const prevBtn = document.getElementById('prevTestimonial');
    const nextBtn = document.getElementById('nextTestimonial');
    const dots = document.querySelectorAll('.testimonial-dot');
    
    let currentIndex = 0;
    const testimonialCount = document.querySelectorAll('.testimonial-card').length;
    
    // Function to update the slider position
    function updateSlider() {
        const translateX = -(currentIndex * 33.333333);
        slider.style.transform = `translateX(${translateX}%)`;
        
        // Update active dot
        dots.forEach((dot, index) => {
            if (index === currentIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }
    
    // Previous button click
    prevBtn.addEventListener('click', function() {
        currentIndex = (currentIndex === 0) ? testimonialCount - 1 : currentIndex - 1;
        updateSlider();
    });
    
    // Next button click
    nextBtn.addEventListener('click', function() {
        currentIndex = (currentIndex === testimonialCount - 1) ? 0 : currentIndex + 1;
        updateSlider();
    });
    
    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', function() {
            currentIndex = index;
            updateSlider();
        });
    });
    
    // Auto-advance testimonials every 5 seconds
    setInterval(function() {
        currentIndex = (currentIndex === testimonialCount - 1) ? 0 : currentIndex + 1;
        updateSlider();
    }, 5000);
    
    // Initialize slider
    updateSlider();
    
    // FAQ functionality
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        const icon = item.querySelector('.faq-question i');
        
        question.addEventListener('click', function() {
            const isActive = item.classList.contains('active');
            
            // Close all other FAQ items
            faqItems.forEach(otherItem => {
                otherItem.classList.remove('active');
                const otherIcon = otherItem.querySelector('.faq-question i');
                otherIcon.classList.remove('fa-chevron-up');
                otherIcon.classList.add('fa-chevron-down');
            });
            
            // Toggle current item
            if (!isActive) {
                item.classList.add('active');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        });
    });
});
</script>

<?php include('includes/footer.php'); ?>