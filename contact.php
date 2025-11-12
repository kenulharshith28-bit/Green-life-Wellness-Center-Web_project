<?php include('header.php'); ?>
<link rel="stylesheet" href="includes/contact.css">
<link rel="stylesheet" href="includes/footer.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Hero Section -->
<section class="contact-hero">
  <div class="contact-hero-text" style="animation: fadeIn 2s ease-in-out;">
    <h1>Contact Us</h1>
    <p>We’re here to support your wellness journey. Call, message, or email us for any inquiries or support.</p>
  </div>
</section>

<!-- Contact Section -->
<section class="contact-section">
  <div class="contact-container">

    <!-- Contact Info -->
    <div class="contact-info">
      <h3>We will be in touch shortly.</h3>
      <p>Reach out to book your healing journey. Call, email, or visit us for personalized Ayurvedic care and serene wellness experiences.</p>

      <div class="info-row"><i class="fas fa-map-marker-alt"></i>
        <span>No.123 Wellness Road, Colombo</span>
      </div>
      <div class="info-row"><i class="fas fa-envelope"></i>
        <a href="mailto:colombo@greenlifewellness.lk">colombo@greenlifewellness.lk</a>
      </div>
      <div class="info-row"><i class="fas fa-phone"></i>
        <a href="tel:+94774556177">+94 77 455 6177</a>
      </div>
      <div class="info-row"><i class="fab fa-whatsapp"></i>
        <a href="https://wa.me/94774556177" target="_blank">+94 77 455 6177</a>
      </div>
    </div>

    <!-- Contact Form -->
    <div class="contact-form">
      <h3>Write to us</h3>
      <?php
      $msg = "";
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        include('db.php');
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $message = $conn->real_escape_string($_POST['message']);

        $sql = "INSERT INTO contacts (name, email, message) VALUES ('$name', '$email', '$message')";
        if ($conn->query($sql)) {
          $msg = "<p class='success'>Message sent successfully!</p>";
        } else {
          $msg = "<p class='error'>Error: Could not send message.</p>";
        }
        $conn->close();
      }
      echo $msg;
      ?>
      <form action="" method="POST">
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <textarea name="message" placeholder="Message" rows="6" required></textarea>
        <button type="submit">Send Message</button>
      </form>
    </div>

  </div>
</section>

<!-- Google Map Section (Full-width) -->
<section class="map-section">
  <iframe
    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126743.05977123854!2d79.79196375221406!3d6.927078546429698!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae2594c22eb4ac7%3A0x6d88983e7ae46f91!2sColombo!5e0!3m2!1sen!2slk!4v1718793823476!5m2!1sen!2slk"
    class="google-map" allowfullscreen="" loading="lazy"
    referrerpolicy="no-referrer-when-downgrade"></iframe>
</section>

<?php include('footer.php'); ?>
