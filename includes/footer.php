</main>
<!-- Footer -->
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-section about">
            <h4>About Us</h4>
            <p>E-Learning Platform is dedicated to providing quality online education for everyone, anytime, anywhere.</p>
        </div>
        <div class="footer-section links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>
        <div class="footer-section contact">
            <h4>Contact Us</h4>
            <p>Email: support@elearning.com</p>
            <p>Phone: +91 9876543210</p>
            <p>Address: 123 Knowledge St, Education City</p>
        </div>
        <div class="footer-section social">
            <h4>Follow Us</h4>
            <div class="social-icons">
                <a href="#" target="_blank">Facebook</a>
                <a href="#" target="_blank">Twitter</a>
                <a href="#" target="_blank">Instagram</a>
                <a href="#" target="_blank">LinkedIn</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo date('Y'); ?> E-Learning Platform. All Rights Reserved.
    </div>
</footer>

<style>
    /* Footer Styling */
    .site-footer {
        background: #1a1a1a;
        color: #fff;
        padding: 50px 20px 20px;
        font-size: 14px;
        line-height: 1.6;
    }

    .footer-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .footer-section h4 {
        color: #ff7e5f;
        margin-bottom: 15px;
        font-size: 18px;
    }

    .footer-section p, .footer-section ul, .footer-section li {
        color: #ccc;
        margin: 5px 0;
    }

    .footer-section ul {
        list-style: none;
        padding: 0;
    }

    .footer-section li a {
        color: #ccc;
        transition: color 0.3s;
    }

    .footer-section li a:hover {
        color: #ff7e5f;
    }

    .social-icons a {
        display: inline-block;
        margin-right: 10px;
        padding: 8px 12px;
        background: #ff7e5f;
        color: #fff;
        border-radius: 5px;
        transition: background 0.3s, transform 0.3s;
        font-size: 14px;
    }

    .social-icons a:hover {
        background: #feb47b;
        transform: translateY(-2px);
    }

    .footer-bottom {
        text-align: center;
        margin-top: 30px;
        border-top: 1px solid #333;
        padding-top: 15px;
        color: #888;
        font-size: 13px;
    }

    /* Responsive */
    @media(max-width: 768px){
        .footer-container {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .social-icons a { margin-bottom: 10px; }
    }
</style>

<script src="/elearning-platform/assets/js/script.js"></script>
</body>
</html>
