<?php
$conn = mysqli_connect("localhost", "root", "", "pharmacare");

if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}

$message = "";

if (isset($_POST['send_request'])) {
  if (isset($_FILES['prescription_image']) && $_FILES['prescription_image']['error'] === 0) {

    $file_name = $_FILES['prescription_image']['name'];
    $file_tmp = $_FILES['prescription_image']['tmp_name'];

    $upload_dir = "images/prescriptions/";
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0777, true);
    }

    $new_file_name = time() . "_" . basename($file_name);
    $db_image_path = $upload_dir . $new_file_name;

    $allowed_ext = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext)) {
      $message = "<script>alert('Invalid file type. Only JPG and PNG allowed.');</script>";
    } else {
      if (move_uploaded_file($file_tmp, $db_image_path)) {

        $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
        $notes = "Patient: $patient_name, Phone: $phone";

        $sql = "INSERT INTO prescription (image_url, notes, status) 
                        VALUES ('$db_image_path', '$notes', 'pending')";

        if (mysqli_query($conn, $sql)) {
          $message = "<script>alert('Prescription uploaded successfully!');</script>";
        } else {
          $message = "<script>alert('Database Error: " . mysqli_error($conn) . "');</script>";
        }
      } else {
        $message = "<script>alert('Failed to upload file to server.');</script>";
      }
    }

  } else {
    $message = "<script>alert('Please select a valid image.');</script>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PharmaCare • Home</title>
  <link rel="icon" href="images/icon-pharmacare.png" type="image/x-icon">
  <link rel="stylesheet" href="home.css" />
</head>

<body>

  <!-- ========================== NAVBAR ========================== -->
  <nav class="main-navbar">
    <div class="logo">💊 PharmaCare</div>

    <ul class="nav-links">
      <li><a href="home.php" class="active">Home</a></li>
      <li><a href="product.php">Products</a></li>
      <li><a href="contact.html">Contact</a></li>
      <li><a href="about.html">About us</a></li>
      <li><a href="login.php">Login</a></li>
    </ul>

    <div class="cart-icon">
      <a href="cart.php">🛒 <span id="cart-count">0</span></a>
    </div>
  </nav>

  <!-- ========================== HERO SECTION ========================== -->
  <header class="hero">
    <div class="container hero-grid">

      <!-- Hero Text -->
      <div class="hero-text">
        <p class="hero-pill">Online pharmacy • 24/7</p>

        <h1>Trusted medicines, clear design, and safe delivery.</h1>

        <p class="hero-subtitle">
          Order prescription and OTC medicines in a few clicks.
          Clean interface, clear prices, and a pharmacist-friendly experience.
        </p>

        <div class="hero-actions">
          <a href="product.php" class="btn btn-primary">Browse medicines</a>
          <a href="#prescription" class="btn btn-outline">Upload prescription</a>
        </div>

        <div class="hero-meta">
          <span>✔ Verified products</span>
          <span>✔ Licensed pharmacist review</span>
          <span>✔ Fast home delivery</span>
        </div>
      </div>

      <?php
      $sql = "SELECT name, price, image_url FROM product LIMIT 1";
      $result = mysqli_query($conn, $sql);
      ?>

      <div align="right">
        <?php if ($result && mysqli_num_rows($result) > 0):
          $row = mysqli_fetch_assoc($result);
          
          $imgSrc = $row['image_url'];
          ?>
          <img src="<?php echo htmlspecialchars($imgSrc); ?>" width="500" alt="Product Image">
          <br>
          <strong><?php echo htmlspecialchars($row['name']); ?></strong>
          <br>
          <?php echo htmlspecialchars($row['price']); ?> EGP
          <br>
          <button class="btn-pill">View in store</button>
        <?php else: ?>
          <p>No featured product available.</p>
        <?php endif; ?>
      </div>

    </div>
  </header>

  <main>

    <!-- ========================== BEST SELLERS ========================== -->
    <section class="section" id="best-selling">
      <div class="container">

        <div class="section-heading center">
          <h2>Best-selling medicines</h2>
          <p class="section-subtitle">
            A quick look at the products your patients trust the most.
          </p>
        </div>

        <!-- Row 1 -->
        <div class="home-products-grid">

          <?php
          $sql = "SELECT name, price, image_url, description FROM product LIMIT 6";
          $result = mysqli_query($conn, $sql);

          if ($result && mysqli_num_rows($result) > 0):
            ?>

            <table border="0" align="right" cellpadding="10" cellspacing="10">
              <tr>
                <?php
                $count = 0;
                while ($row = mysqli_fetch_assoc($result)) {
                  $count++;
                  $imgSrc = $row['image_url'];
                  ?>
                  <td valign="top" align="left" width="33%">
                    <div>
                      <img src="<?php echo htmlspecialchars($imgSrc); ?>" width="300"
                        style="max-width:100%; height:auto; border-radius:10px;">
                      <br>
                      <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                      <span style="color:#27ae60; font-weight:bold;"><?php echo htmlspecialchars($row['price']); ?>
                        EGP</span><br>
                      <small style="color:#666; display:block; height:60px; overflow:hidden;">
                        <?php echo substr(htmlspecialchars($row['description']), 0, 100) . '...'; ?>
                      </small>
                      <br>
                      <button class="btn-pill">View in store</button>
                    </div>
                  </td>
                  <?php
                  if ($count % 3 == 0) {
                    echo "</tr><tr>";
                  }
                }
                ?>
              </tr>
            </table>

          <?php else: ?>
            <p style="text-align:center;">No products found.</p>
          <?php endif; ?>

        </div>

        <div class="section-cta center">
          <a href="product.php" class="btn btn-outline">Browse all products</a>
        </div>

      </div>
    </section>

    <!-- ========================== PRESCRIPTION UPLOAD ========================== -->
    <section class="section section-muted" id="prescription">
      <div class="container prescription-grid">
        <div class="prescription-text">
          <h2>Upload your prescription</h2>
          <p class="section-subtitle">Upload a clear image and our pharmacists will prepare your order.</p>
          <ul class="prescription-list">
            <li>✔ Handled by licensed pharmacists</li>
            <li>✔ Interaction check & alternatives review</li>
            <li>✔ Confirmed before shipping</li>
          </ul>
        </div>

        <form id="prescriptionForm" class="prescription-card" action="home.php" method="POST"
          enctype="multipart/form-data">
          <h3>Prescription image</h3>
          <p class="prescription-hint">Accepted: JPG, PNG. Make sure details are clear.</p>

          <div class="file-drop">
            <label for="prescription-file">
              <span class="file-icon">📷</span>
              <span class="file-text">Drag & drop or <span class="file-link">browse</span></span>
            </label>
            <input type="file" id="prescription-file" name="prescription_image" accept="image/*" required />
          </div>

          <!-- Live Preview -->
          <div id="image-preview" style="margin-top:10px; display:none;">
            <p>Image Preview:</p>
            <img id="preview-img" src="" alt="Prescription Preview" style="max-width:300px; border-radius:5px;">
          </div>

          <div class="prescription-fields">
            <div class="field">
              <label>Patient name</label>
              <input type="text" id="patient-name" name="patient_name" placeholder="e.g. Ahmed Mostafa" required />
            </div>
            <div class="field">
              <label>Phone number</label>
              <input type="tel" id="phone-number" name="phone_number" placeholder="e.g. 0100 000 0000" required />
            </div>
          </div>

          <button type="submit" class="btn btn-primary full-width">Send prescription request</button>
          <p class="tiny-note">This is a demo — data is saved to DB.</p>
        </form>
      </div>
    </section>

    <!-- ========================== WHY CHOOSE US ========================== -->
    <section class="section">
      <div class="container">
        <div class="section-heading center">
          <h2>Why choose PharmaCare?</h2>
          <p class="section-subtitle">A guided, safe and modern experience.</p>
        </div>
        <div class="features-grid">
          <article class="feature-card">
            <h3>Verified medicines</h3>
            <p>Trusted suppliers with licensed sources.</p>
          </article>
          <article class="feature-card">
            <h3>Pharmacist-friendly design</h3>
            <p>Clear names, strengths, and indications.</p>
          </article>
          <article class="feature-card">
            <h3>Fast & traceable delivery</h3>
            <p>Track your order from confirmation to delivery.</p>
          </article>
        </div>
      </div>
    </section>

    <!-- ========================== STATISTICS ========================== -->
    <section class="section section-tight">
      <div class="container">
        <div class="stats-strip">
          <div class="stat">
            <div class="stat-value">99.8%</div>
            <div class="stat-label">Customer satisfaction</div>
          </div>
          <div class="stat">
            <div class="stat-value">5,000+</div>
            <div class="stat-label">Available products</div>
          </div>
          <div class="stat">
            <div class="stat-value">24h</div>
            <div class="stat-label">Avg. delivery time</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ========================== SHOP BY CATEGORY ========================== -->
    <section class="section">
      <div class="container">
        <div class="section-heading center">
          <h2>Shop by category</h2>
          <p class="section-subtitle">Jump to the treatment type you need.</p>
        </div>
        <div class="category-grid">
          <a href="product.php" class="category-card">
            <h3>Cardio & blood pressure</h3>
            <p>Brilique, Bisocard, and more.</p>
          </a>
          <a href="product.php" class="category-card">
            <h3>Antibiotics</h3>
            <p>Augmentin, Amebazole, and others.</p>
          </a>
          <a href="product.php" class="category-card">
            <h3>Pain & inflammation</h3>
            <p>Dantrelax, Mark Fast, and others.</p>
          </a>
          <a href="product.php" class="category-card">
            <h3>Supplements & vitamins</h3>
            <p>Calcitron, Omega-3, Neuroton and more.</p>
          </a>
        </div>
      </div>
    </section>

    <!-- ========================== FINAL CTA ========================== -->
    <section class="section section-tight">
      <div class="container">
        <div class="cta-banner">
          <div>
            <h2>Need help choosing a product?</h2>
            <p>Contact our pharmacists for prescription review or chronic medication planning.</p>
          </div>
          <a href="contact.html" class="btn btn-primary">Contact a pharmacist</a>
        </div>
      </div>
    </section>

  </main>

  <!-- ========================== FOOTER ========================== -->
  <footer class="main-footer">
    <div class="footer-container">
      <div class="footer-column">
        <h3>PharmaCare</h3>
        <p>Your trusted source for medicines and healthcare products.</p>
      </div>
      <div class="footer-column">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="home.php">Home</a></li>
          <li><a href="product.php">Products</a></li>
          <li><a href="contact.html">Contact</a></li>
          <li><a href="about.html">About Us</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h4>Contact</h4>
        <p>Email: support@Pharma.com</p>
        <p>Phone: +20 123 456 789</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 Pharma. All Rights Reserved.</p>
    </div>
  </footer>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const form = document.getElementById('prescriptionForm');
      const fileInput = document.getElementById('prescription-file');
      const nameInput = document.getElementById('patient-name');
      const phoneInput = document.getElementById('phone-number');
      const previewDiv = document.getElementById('image-preview');
      const previewImg = document.getElementById('preview-img');

      // Live image preview
      fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
          const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
          if (!allowedTypes.includes(file.type)) {
            alert("Invalid file type. Only JPG and PNG allowed.");
            fileInput.value = "";
            previewDiv.style.display = 'none';
            return;
          }
          const reader = new FileReader();
          reader.onload = function (e) {
            previewImg.src = e.target.result;
            previewDiv.style.display = 'block';
          }
          reader.readAsDataURL(file);
        } else {
          previewDiv.style.display = 'none';
        }
      });

      // Form validation
      form.addEventListener('submit', function (e) {
        let valid = true;

        // Clear previous errors
        document.querySelectorAll('.error-msg').forEach(el => el.remove());
        [nameInput, phoneInput, fileInput].forEach(el => el.style.border = "");

        // Name validation (letters + spaces)
        const nameValue = nameInput.value.trim();
        const nameRegex = /^[A-Za-z\u0600-\u06FF ]+$/;
        if (!nameRegex.test(nameValue)) {
          valid = false;
          nameInput.style.border = "2px solid red";
          const p = document.createElement('p');
          p.className = 'error-msg';
          p.style.color = 'red';
          p.style.fontSize = '12px';
          p.textContent = "Enter a valid name (letters only)";
          nameInput.parentNode.appendChild(p);
        } else {
          nameInput.style.border = "2px solid green";
        }

        // Phone validation (11 digits)
        const phoneValue = phoneInput.value.trim();
        const phoneRegex = /^\d{11}$/;
        if (!phoneRegex.test(phoneValue)) {
          valid = false;
          phoneInput.style.border = "2px solid red";
          const p = document.createElement('p');
          p.className = 'error-msg';
          p.style.color = 'red';
          p.style.fontSize = '12px';
          p.textContent = "Enter a valid phone number (11 digits)";
          phoneInput.parentNode.appendChild(p);
        } else {
          phoneInput.style.border = "2px solid green";
        }

        // File validation
        if (fileInput.files.length === 0) {
          valid = false;
          fileInput.style.border = "2px solid red";
          const p = document.createElement('p');
          p.className = 'error-msg';
          p.style.color = 'red';
          p.style.fontSize = '12px';
          p.textContent = "Please upload a prescription image";
          fileInput.parentNode.appendChild(p);
        } else {
          fileInput.style.border = "2px solid green";
        }

        if (!valid) e.preventDefault(); // prevent submission if errors
      });
    });
  </script>

</body>

</html>