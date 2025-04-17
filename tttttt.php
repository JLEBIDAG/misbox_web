<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Icon-Based Control Panel</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/annyang/2.6.0/annyang.min.js"></script> <!-- Voice recognition library -->
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Arial', sans-serif;
    }
    .btn-custom {
      padding: 15px 25px;
      font-size: 30px;
      border-radius: 12px;
      width: 80px;
      height: 80px;
    }
    .control-btns {
      margin-top: 20px;
    }
    .status {
      font-size: 18px;
      margin-top: 20px;
      font-weight: bold;
    }
    .active {
      color: green;
    }
    .inactive {
      color: red;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .btn-custom {
        padding: 15px;
        font-size: 25px;
        width: 70px;
        height: 70px;
      }
      .control-btns {
        margin-top: 10px;
      }
    }

    @media (max-width: 576px) {
      .btn-custom {
        padding: 10px;
        font-size: 20px;
        width: 60px;
        height: 60px;
      }
      .control-btns {
        margin-top: 10px;
      }
    }
  </style>
</head>
<body>

  <div class="container mt-5 text-center">
    <h2 class="mb-4">Control Panel</h2>

    <div class="row justify-content-center control-btns">
      <div class="col-3 col-sm-2 mb-3">
        <button class="btn btn-primary btn-custom" id="backBtn">
          <i class="fas fa-arrow-down"></i>
        </button>
      </div>
      <div class="col-3 col-sm-2 mb-3">
        <button class="btn btn-primary btn-custom" id="forwardBtn">
          <i class="fas fa-arrow-up"></i>
        </button>
      </div>
    </div>

    <div class="row justify-content-center control-btns">
      <div class="col-3 col-sm-2 mb-3">
        <button class="btn btn-primary btn-custom" id="leftBtn">
          <i class="fas fa-arrow-left"></i>
        </button>
      </div>
      <div class="col-3 col-sm-2 mb-3">
        <button class="btn btn-primary btn-custom" id="rightBtn">
          <i class="fas fa-arrow-right"></i>
        </button>
      </div>
    </div>

    <div class="row justify-content-center control-btns">
      <div class="col-3 col-sm-3 mb-3">
        <button class="btn btn-success btn-custom" id="voiceBtn">
          <i class="fas fa-microphone"></i>
        </button>
      </div>
    </div>

    <div id="voiceStatus" class="status inactive">Voice Control: Inactive</div>

  </div>

  <script>
    // Button click events
    document.getElementById('backBtn').addEventListener('click', () => alert('Moving Back'));
    document.getElementById('forwardBtn').addEventListener('click', () => alert('Moving Forward'));
    document.getElementById('leftBtn').addEventListener('click', () => alert('Turning Left'));
    document.getElementById('rightBtn').addEventListener('click', () => alert('Turning Right'));

    // Voice activation setup
    const commands = {
      'move forward': () => {
        document.getElementById('voiceStatus').textContent = 'Voice Command: Moving Forward';
        document.getElementById('voiceStatus').classList.remove('inactive');
        document.getElementById('voiceStatus').classList.add('active');
      },
      'move back': () => {
        document.getElementById('voiceStatus').textContent = 'Voice Command: Moving Back';
        document.getElementById('voiceStatus').classList.remove('inactive');
        document.getElementById('voiceStatus').classList.add('active');
      },
      'turn left': () => {
        document.getElementById('voiceStatus').textContent = 'Voice Command: Turning Left';
        document.getElementById('voiceStatus').classList.remove('inactive');
        document.getElementById('voiceStatus').classList.add('active');
      },
      'turn right': () => {
        document.getElementById('voiceStatus').textContent = 'Voice Command: Turning Right';
        document.getElementById('voiceStatus').classList.remove('inactive');
        document.getElementById('voiceStatus').classList.add('active');
      },
    };

    if (annyang) {
      annyang.addCommands(commands);
    }

    // Handle Voice Activation button click
    let voiceActive = false;
    document.getElementById('voiceBtn').addEventListener('click', () => {
      if (!voiceActive) {
        annyang.start();
        voiceActive = true;
        document.getElementById('voiceStatus').textContent = "Voice Control: Active";
        document.getElementById('voiceStatus').classList.remove('inactive');
        document.getElementById('voiceStatus').classList.add('active');
        document.getElementById('voiceBtn').innerHTML = '<i class="fas fa-microphone-slash"></i>';
      } else {
        annyang.abort();
        voiceActive = false;
        document.getElementById('voiceStatus').textContent = "Voice Control: Inactive";
        document.getElementById('voiceStatus').classList.remove('active');
        document.getElementById('voiceStatus').classList.add('inactive');
        document.getElementById('voiceBtn').innerHTML = '<i class="fas fa-microphone"></i>';
      }
    });
  </script>

  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
