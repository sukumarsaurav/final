// Debug script to check if plans are being filtered correctly
document.addEventListener('DOMContentLoaded', function() {
    console.log('Membership tabs script loaded');
    
    // Check if plans exist in each tab
    const monthlyPlans = document.querySelectorAll('#monthly-plans .plan-card');
    const quarterlyPlans = document.querySelectorAll('#quarterly-plans .plan-card');
    const annuallyPlans = document.querySelectorAll('#annually-plans .plan-card');
    
    console.log('Monthly plans:', monthlyPlans.length);
    console.log('Quarterly plans:', quarterlyPlans.length);
    console.log('Annually plans:', annuallyPlans.length);
    
    // Fix tab switching if needed
    const tabs = document.querySelectorAll('.tab');
    const cyclePlans = document.querySelectorAll('.cycle-plans');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all plan sections
            cyclePlans.forEach(p => p.classList.remove('active'));
            
            // Show the selected plan section
            const cycle = this.getAttribute('data-cycle');
            const planSection = document.getElementById(`${cycle}-plans`);
            
            if (planSection) {
                planSection.classList.add('active');
                console.log(`Activated ${cycle} plans section`);
            } else {
                console.error(`Could not find plan section with ID: ${cycle}-plans`);
            }
        });
    });
});