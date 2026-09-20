<?php
// Handle JSON request from JS based on chosen location
if (isset($_GET['api_action']) && $_GET['api_action'] === 'menu') {
    header('Content-Type: application/json; charset=utf-8');

    // Map location slugs to synced JSON filenames
    $locationMap = [
        'wright-quad-dining-hall' => 'wright.json',
        'mcnutt-dining-hall'      => 'mcnutt.json',
        'forest-dining-hall'      => 'forest.json',
        'collins-eatery'          => 'collins.json',
        'goodbody-hall-eatery'    => 'goodbody.json'
    ];

    $loc = $_GET['loc'] ?? 'mcnutt-dining-hall';
    $fileName = $locationMap[$loc] ?? 'sample_menu.json';
    $jsonFile = __DIR__ . '/' . $fileName;

    if (file_exists($jsonFile)) {
        echo file_get_contents($jsonFile);
    } else {
        echo json_encode([
            'error' => true,
            'message' => "Menu file ($fileName) not found on Silo yet."
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IU Dining Calorie Tracker</title>
  <style>
    :root {
      --iu-crimson: #990000;
      --bg-dark: #121212;
      --card-bg: #1e1e1e;
      --text: #ffffff;
      --subtext: #a0a0a0;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background-color: var(--bg-dark);
      color: var(--text);
      margin: 0;
      padding: 20px;
    }

    .container {
      max-width: 1000px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1fr 320px;
      gap: 20px;
    }

    @media (max-width: 768px) {
      .container { grid-template-columns: 1fr; }
    }

    header {
      grid-column: 1 / -1;
      background: var(--iu-crimson);
      padding: 15px 20px;
      border-radius: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    header h1 { margin: 0; font-size: 1.5rem; }

    .card {
      background: var(--card-bg);
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    }

    .controls {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 10px;
      margin-bottom: 20px;
    }

    select, input, button {
      background: #2d2d2d;
      color: var(--text);
      border: 1px solid #444;
      padding: 10px;
      border-radius: 6px;
      width: 100%;
      box-sizing: border-box;
    }

    button {
      background: var(--iu-crimson);
      color: white;
      font-weight: bold;
      cursor: pointer;
      border: none;
    }

    .menu-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px;
      border-bottom: 1px solid #333;
    }

    .item-meta { font-size: 0.85rem; color: var(--subtext); }

    .stat-box {
      background: #2a2a2a;
      padding: 15px;
      border-radius: 6px;
      text-align: center;
      margin-bottom: 15px;
    }

    .stat-number { font-size: 2rem; font-weight: bold; color: #4caf50; }

    .logged-item {
      display: flex;
      justify-content: space-between;
      font-size: 0.9rem;
      margin-bottom: 8px;
      padding-bottom: 8px;
      border-bottom: 1px solid #333;
    }

    .btn-remove {
      background: none;
      border: none;
      color: #ff5252;
      cursor: pointer;
      padding: 0 5px;
      width: auto;
    }
  </style>
</head>
<body>

<div class="container">
  <header>
    <h1>IU Dining Calorie Tracker</h1>
    <small>Hosted on Luddy Silo</small>
  </header>

  <main>
    <div class="card">
      <h2>Find Food</h2>
      <div class="controls">
        <select id="locationSelect">
           <option value="wright-quad-dining-hall">Wright Quad Dining Hall</option>
           <option value="mcnutt-dining-hall">McNutt Dining Hall</option>
           <option value="forest-dining-hall">Forest Dining Hall</option>
           <option value="collins-eatery">Collins Eatery</option>
           <option value="goodbody-hall-eatery">Goodbody Hall Eatery</option>
        </select>
        <select id="mealSelect">
          <option value="lunch">Lunch</option>
          <option value="breakfast">Breakfast</option>
          <option value="dinner">Dinner</option>
        </select>
        <input type="date" id="dateSelect">
        <button id="fetchBtn" onclick="fetchMenu()">Get Menu</button>
      </div>

      <div id="menuContainer">
        <p style="color: var(--subtext);">Select options above and click "Get Menu" to display items.</p>
      </div>
    </div>
  </main>

  <aside class="tracker-summary">
    <div class="card">
      <h2>Daily Log</h2>
      <div class="stat-box">
        <div>Total Calories</div>
        <div class="stat-number" id="totalCals">0</div>
      </div>
      
      <h3>Logged Items</h3>
      <div id="loggedItems">
        <p style="color: var(--subtext); font-size: 0.9rem;">No items added yet.</p>
      </div>
      <button onclick="clearLog()" style="margin-top: 15px; background: #444;">Clear Today's Log</button>
    </div>
  </aside>
</div>

<script>
  let loggedFood = JSON.parse(localStorage.getItem('iu_cals_log')) || [];

  document.getElementById('dateSelect').valueAsDate = new Date();

async function fetchMenu() {
    const loc = document.getElementById('locationSelect').value;
    const container = document.getElementById('menuContainer');
    container.innerHTML = '<p>Loading synced menu & nutrition data...</p>';

    try {
      // Pass the selected location slug to PHP
      const res = await fetch(`index.php?api_action=menu&loc=${loc}`);
      const data = await res.json();

      if (data.error) {
        throw new Error(data.message);
      }

      renderMenu(data);
    } catch (err) {
      console.error("Error reading menu JSON:", err);
      container.innerHTML = `<p style="color:#ff5252;">⚠️ Unable to load menu data (${err.message}).</p>`;
    }
  }

  function renderMenu(data) {
    const container = document.getElementById('menuContainer');
    container.innerHTML = '';

    let itemsFound = false;

    // Traverse Nutrislice days -> menu_items -> food
    if (data && data.days && Array.isArray(data.days)) {
      data.days.forEach(day => {
        if (day.menu_items && Array.isArray(day.menu_items)) {
          day.menu_items.forEach(item => {
            if (!item.food || !item.food.name) return;

            itemsFound = true;
            const food = item.food;
            
            // Extract nutrition info safely
            const cals = food.rounded_nutrition_info?.calories ?? food.nutrition_info?.calories ?? 0;
            const protein = food.rounded_nutrition_info?.g_protein ?? food.nutrition_info?.g_protein ?? 'N/A';
            const carbs = food.rounded_nutrition_info?.g_carbs ?? food.nutrition_info?.g_carbs ?? 'N/A';
            const fat = food.rounded_nutrition_info?.g_fat ?? food.nutrition_info?.g_fat ?? 'N/A';
            const size = food.serving_size ? `(${food.serving_size})` : '';

            const div = document.createElement('div');
            div.className = 'menu-item';
            div.innerHTML = `
              <div class="item-info">
                <h4 style="margin: 0 0 5px 0;">${food.name} <small style="color:#aaa">${size}</small></h4>
                <div class="item-meta">
                  <strong>${cals} Cals</strong> | P: ${protein}g | C: ${carbs}g | F: ${fat}g
                </div>
              </div>
              <button onclick="addFood('${escapeQuotes(food.name)}', ${cals})" style="width: auto;">+ Add</button>
            `;
            container.appendChild(div);
          });
        }
      });
    }

    if (!itemsFound) {
      container.innerHTML = '<p>No menu items found in the synced JSON file.</p>';
    }
  }

  function escapeQuotes(str) {
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
  }

  function addFood(name, cals) {
    loggedFood.push({ id: Date.now(), name, cals });
    saveAndRenderLog();
  }

  function removeFood(id) {
    loggedFood = loggedFood.filter(item => item.id !== id);
    saveAndRenderLog();
  }

  function clearLog() {
    loggedFood = [];
    saveAndRenderLog();
  }

  function saveAndRenderLog() {
    localStorage.setItem('iu_cals_log', JSON.stringify(loggedFood));
    
    const container = document.getElementById('loggedItems');
    const totalEl = document.getElementById('totalCals');
    
    container.innerHTML = '';
    let totalCals = 0;

    if (loggedFood.length === 0) {
      container.innerHTML = '<p style="color: var(--subtext); font-size: 0.9rem;">No items added yet.</p>';
    } else {
      loggedFood.forEach(item => {
        totalCals += item.cals;
        const div = document.createElement('div');
        div.className = 'logged-item';
        div.innerHTML = `
          <span>${item.name} (${item.cals} cal)</span>
          <button class="btn-remove" onclick="removeFood(${item.id})">&times;</button>
        `;
        container.appendChild(div);
      });
    }

    totalEl.textContent = totalCals;
  }

  // Load menu automatically when page loads
  fetchMenu();
  saveAndRenderLog();
</script>
</body>
</html>