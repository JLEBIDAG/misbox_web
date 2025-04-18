<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Fixing Database Connection Error</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    .step-box {
      border: 1px solid #ddd;
      border-radius: 1rem;
      padding: 1.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
      transition: 0.3s ease;
    }

    .step-number {
      font-size: 2rem;
      font-weight: bold;
      color: #dc3545;
    }

    .step-image {
      max-height: 200px;
      object-fit: contain;
      border-radius: 0.5rem;
      transition: transform 0.3s ease;
      cursor: zoom-in;
    }

    .step-image:hover {
      transform: scale(1.1);
    }

    .step-title {
      font-size: 1.5rem;
      font-weight: 500;
    }

    .modal-img {
      max-width: 100%;
      height: auto;
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <h2 class="mb-4 text-center text-danger">Database Connection Error – Step-by-Step Fix</h2>

    <!-- STEP 1 -->
    <div class="row step-box align-items-center">
      <div class="col-md-4 text-center">
        <img src="assets/img/troubleShoot/step1.png" class="img-fluid step-image" alt="Step 1" />
      </div>
      <div class="col-md-8">
        <div class="step-number">Step 1</div>
        <div class="step-title">Login to PC-MIS-03</div>
        <p>Open the PC-MIS-03 and login using <strong>misojt</strong> or any local account.</p>
      </div>
    </div>

    <!-- STEP 2 -->
    <div class="row step-box align-items-center flex-md-row-reverse">
      <div class="col-md-4 text-center">
        <img src="assets/img/troubleShoot/step2.png" class="img-fluid step-image" alt="Step 2" />
      </div>
      <div class="col-md-8">
        <div class="step-number">Step 2</div>
        <div class="step-title">Open File Explorer</div>
        <p>Click File Explorer on the taskbar or press <kbd>Windows + E</kbd>.</p>
      </div>
    </div>

    <!-- STEP 3 -->
    <div class="row step-box align-items-center">
      <div class="col-md-4 text-center">
        <img src="assets/img/troubleShoot/step3.png" class="img-fluid step-image" alt="Step 3" />
      </div>
      <div class="col-md-8">
        <div class="step-number">Step 3</div>
        <div class="step-title">Access Local Disk C</div>
        <p>Click/Choose and open the "Local Disk (C:)" drive.</p>
      </div>
    </div>

    <!-- STEP 4 -->
    <div class="row step-box align-items-center flex-md-row-reverse">
      <div class="col-md-4 text-center">
        <img src="assets/img/troubleShoot/step4.png" class="img-fluid step-image" alt="Step 4" />
      </div>
      <div class="col-md-8">
        <div class="step-number">Step 4</div>
        <div class="step-title">Open the xampp Folder</div>
        <p>Find and open the folder named <strong>xampp</strong>.</p>
      </div>
    </div>

    <!-- STEP 5 -->
    <div class="row step-box align-items-center">
      <div class="col-md-4 text-center">
        <img src="assets/img/troubleShoot/step5.png" class="img-fluid step-image" alt="Step 5" />
      </div>
      <div class="col-md-8">
        <div class="step-number">Step 5</div>
        <div class="step-title">Open the mysql Folder</div>
        <p>Inside xampp, locate and open the <strong>mysql</strong> folder.</p>
      </div>
    </div>

    <!-- STEP 6 -->
    <div class="row step-box align-items-center flex-md-row-reverse">
      <div class="col-md-4 text-center">
        <img src="assets/img/troubleShoot/step6.png" class="img-fluid step-image" alt="Step 6" />
      </div>
      <div class="col-md-8">
        <div class="step-number">Step 6</div>
        <div class="step-title">Copy the Data Folder</div>
        <p>Select the <strong>data</strong> folder and click the "Copy" button at the top left.</p>
      </div>
    </div>

    <!-- STEP 7 -->
    <div class="row step-box align-items-center">
      <div class="col-md-4 text-center">
        <img src="assets/img/troubleShoot/step7.png" class="img-fluid step-image" alt="Step 7" />
      </div>
      <div class="col-md-8">
        <div class="step-number">Step 7</div>
        <div class="step-title">Paste and Rename</div>
        <p>Paste the folder and rename it using this format: <code>data_oldMM-DD-YY</code>.</p>
      </div>
    </div>

    <!-- STEP 8 -->
    <div class="row step-box align-items-center flex-md-row-reverse">
      <div class="col-md-4 text-center">
        <img src="assets/img/troubleShoot/step8.png" class="img-fluid step-image" alt="Step 8" />
      </div>
      <div class="col-md-8">
        <div class="step-number">Step 8</div>
        <div class="step-title">Clear the Data Folder</div>
        <p>Open the <strong>data</strong> folder again and delete all the files inside it.</p>
      </div>
    </div>

    <!-- STEP 9 -->
    <div class="row step-box align-items-center">
      <div class="col-md-4 text-center">
        <img src="assets/img/troubleShoot/step9.png" class="img-fluid step-image" alt="Step 9" />
      </div>
      <div class="col-md-8">
        <div class="step-number">Step 9</div>
        <div class="step-title">Copy Backup Files</div>
        <p>Go to the <strong>backup</strong> folder and copy all files <strong>except</strong> <code>ibdata1</code>.</p>
      </div>
    </div>

    <!-- STEP 10 -->
    <div class="row step-box align-items-center flex-md-row-reverse">
      <div class="col-md-4 text-center">
        <img src="assets/img/troubleShoot/step10.png" class="img-fluid step-image" alt="Step 10" />
      </div>
      <div class="col-md-8">
        <div class="step-number">Step 10</div>
        <div class="step-title">Paste Files in Data</div>
        <p>Paste the copied backup files into the <strong>data</strong> folder.</p>
      </div>
    </div>

    <!-- FINAL STEP -->
    <div class="text-center mt-4">
      <h4 class="text-success">🎉 All Done!</h4>
      <p>Now reload or open the website: <a href="http://192.168.0.15/MISBOX" target="_blank">http://192.168.0.15/MISBOX</a> and try logging in.</p>
    </div>

    <!-- CONTACT SUPER ADMIN -->
    <div class="mt-5 p-4 bg-light rounded shadow-sm">
      <h4 class="text-primary mb-3">Need Help? Contact the Super Administrator</h4>
      <ul class="list-unstyled">
        <li><strong>Facebook:</strong> <a href="https://www.facebook.com/jian.rence" target="_blank">facebook.com/jian.rence</a></li>
        <li><strong>Mobile Number:</strong> 09127339200</li>
        <li><strong>Viber QR:</strong></li>
        <li>
          <img src="assets/img/troubleShoot/myQr.jpg" alt="Viber QR Code" class="img-fluid mt-2 step-image" style="max-width: 150px;" />
        </li>
      </ul>
    </div>
  </div>

  <!-- Image Modal -->
  <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-body p-0">
          <img src="" class="modal-img w-100" id="modalImage" alt="Enlarged Step Image" />
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.querySelectorAll('.step-image').forEach(img => {
      img.addEventListener('click', () => {
        const modalImage = document.getElementById('modalImage');
        modalImage.src = img.src;
        const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
        imageModal.show();
      });
    });
  </script>
</body>
</html>
