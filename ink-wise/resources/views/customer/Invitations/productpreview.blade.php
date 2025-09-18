<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Save the Date Cards Preview</title>
  <link rel="stylesheet" href="styles.css">
</head>
<link rel="stylesheet" href="{{ asset('css/customer/preview.css') }}">
   <script src="{{ asset('js/customer/preview.js') }}" defer></script>

   
<body>
  <div class="container">

    <!-- LEFT SIDE: PRODUCT PREVIEW -->
    <div class="preview">
      <img id="cardImage" src="{{ asset('customerimages/invite/wedding3.jpg') }}" alt="Card Preview">
      <div class="toggle-buttons">
        <button id="frontBtn" class="active">Front</button>
        <button id="backBtn">Back</button>
      </div>
    </div>

    <!-- RIGHT SIDE: PRODUCT INFO -->
    <div class="details">
      <h2>Save the Date Cards</h2>
      <p class="price">As low as <span class="new-price">₱39</span> per piece</p>
      <p class="delivery">Get it as soon as <b>Tuesday, Sep 16th</b> to 2025</p>

      <h3>Colors</h3>
      <div class="colors">
        <button class="color-btn lightorange active"
          style="background: #f1eee9"
          data-front="{{ asset('customerimages/invite/wedding3.jpg') }}"
          data-back="{{ asset('customerimages/invite/wed1.png') }}"></button>
        <button class="color-btn darkbrown"
          style="background: #b49a6a"
          data-front="{{ asset('customerimages/invite/wedding3.jpg') }}"
          data-back="{{ asset('customerimages/invite/wed2.png') }}"></button>
        <button class="color-btn grayorange"
          style="background:  #230e00"
          data-front="{{ asset('customerimages/invite/wedding3.jpg') }}"
          data-back="{{ asset('customerimages/invite/wed3.png') }}"></button>
      </div>

      <h3>Trim</h3>
      <div class="trims">
        <div class="trim-option active">Standard<br><span>₱30</span></div>
        <div class="trim-option">Rounded<br><span>₱50</span></div>
        <div class="trim-option">Ticket<br><span>₱80</span></div>
      </div>

      <a href="{{ route('design.edit') }}" class="edit-btn">Edit my design</a>
    </div>
  </div>


</body>
</html>
