<?php
// ============================================================================
// PHP BACKEND: API ENDPOINT FOR MENU JSON
// ============================================================================
if (isset($_GET['api_action']) &&$_GET['api_action'] === 'menu') {
    header('Content-Type: application/json; charset=utf-8');

    // Map frontend slugs to local JSON filenames created by sync_menu.py
    $locationMap = [
        'mcnutt-dining-hall'   => 'mcnutt.json',
        'forest-dining-hall'   => 'forest.json',
        'wright-eatery'        => 'wright.json',
        'collins-eatery'       => 'collins.json',
        'goodbody-hall-eatery' => 'goodbody.json'
    ];

    $loc = $_GET['loc'] ?? 'mcnutt-dining-hall';$fileName = $locationMap[$loc] ?? 'mcnutt.json';
    $jsonFile = __DIR__ . '/' .$fileName;

    if (file_exists($jsonFile)) {
        echo file_get_contents($jsonFile);
    } else {
        echo json_encode([
            'error' => true,
            'message' => "Menu file ($fileName) not found on server yet."
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
  <title>IU Dining Hall Calorie & Macro Tracker</title>
  <style>
    :root {
      --iu-crimson: #990000;
      --iu-cream: #EEEDEB;
      --bg-color: #f8f9fa;
      --card-bg: #ffffff;
      --text-dark: #212529;
      --text-muted: #6c757d;
      --border-color: #dee2e6;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background-color: var(--bg-color);
      color: var(--text-dark);
      margin: 0;
      padding: 0;
      padding-bottom: 70px; /* Space for sticky bar */
    }

    header {
      background-color: var(--iu-crimson);
      color: white;
      padding: 1.5rem 1rem;
      text-align: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    header h1 {
      margin: 0;
      font-size: 1.8rem;
    }

    .container {
      max-width: 1000px;
      margin: 1.5rem auto;
      padding: 0 1rem;
    }

    .controls-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1rem;
      background: var(--card-bg);
      padding: 1.25rem;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      margin-bottom: 2rem;
    }

    @media (min-width: 768px) {
      .controls-grid {
        grid-template-columns: 1fr 1fr 1fr;
      }
    }

    .control-group {
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
    }

    .control-group label {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
    }

    select, input[type="text"] {
      padding: 0.6rem 0.8rem;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.2s;
    }

    select:focus, input[type="text"]:focus {
      border-color: var(--iu-crimson);
    }

    .station-group {
      margin-bottom: 2.5rem;
    }

    .station-title {
      font-size: 1.3rem;
      color: var(--iu-crimson);
      border-bottom: 2px solid var(--iu-crimson);
      padding-bottom: 0.4rem;
      margin-bottom: 1rem;
    }

    .food-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1rem;
    }

    .food-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 6px;
      padding: 1rem;
      box-shadow: 0 1px 2px rgba(0,0,0,0.04);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .food-card h4 {
      margin: 0 0 0.75rem 0;
      font-size: 1.05rem;
    }

    .calories-badge {
      display: inline-block;
      background: var(--iu-cream);
      color: var(--text-dark);
      font-weight: 700;
      padding: 0.3rem 0.6rem;
      border-radius: 4px;
      font-size: 0.9rem;
      margin-bottom: 0.6rem;
      align-self: flex-start;
    }

    .macro-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0.4rem;
      margin-top: 0.5rem;
      padding-top: 0.5rem;
      border-top: 1px dashed var(--border-color);
      font-size: 0.8rem;
      text-align: center;
    }

    .macro-item {
      background-color: var(--bg-color);
      padding: 0.3rem 0.2rem;
      border-radius: 4px;
    }

    .macro-label {
      display: block;
      color: var(--text-muted);
      font-size: 0.7rem;
      text-transform: uppercase;
      font-weight: 600;
    }

    .macro-value {
      font-weight: 600;
      color: var(--text-dark);
    }

    .loading-spinner {
      text-align: center;
      padding: 3rem;
      color: var(--text-muted);
      font-size: 1.1rem;
    }

    /* Tracker Bar & Drawer Styling */
    .tracker-bar {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #111111;
      color: white;
      padding: 0.8rem 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 -2px 10px rgba(0,0,0,0.2);
      z-index: 1000;
    }

    .tracker-summary {
      display: flex;
      gap: 1.5rem;
      font-size: 0.95rem;
    }

    .btn-toggle-log {
      background: var(--iu-crimson);
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
    }

    .add-btn {
      margin-top: 0.75rem;
      background: var(--iu-crimson);
      color: white;
      border: none;
      padding: 0.4rem;
      border-radius: 4px;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
      transition: background 0.2s;
    }

    .add-btn:hover {
      background: #7a0000;
    }

    .log-drawer {
      position: fixed;
      bottom: 60px;
      right: 20px;
      width: 320px;
      max-height: 400px;
      background: white;
      border: 1px solid var(--border-color);
      border-radius: 8px 8px 0 0;
      box-shadow: 0 -4px 12px rgba(0,0,0,0.15);
      padding: 1rem;
      overflow-y: auto;
      z-index: 999;
    }

    .log-drawer.hidden {
      display: none;
    }

    .drawer-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .drawer-header h3 {
      margin: 0;
    }

    .clear-btn {
      background: transparent;
      border: none;
      color: #d9534f;
      font-size: 0.8rem;
      cursor: pointer;
      text-decoration: underline;
    }

    .log-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.5rem 0;
      border-bottom: 1px solid var(--border-color);
      font-size: 0.9rem;
    }

    .remove-btn {
      background: transparent;
      color: #d9534f;
      border: none;
      font-weight: bold;
      cursor: pointer;
    }
  </style>
</head>
<body>

  <header>
    <h1>IU Dining Hall Calorie & Macro Tracker</h1>
  </header>

  <div class="container">
    <div class="controls-grid">
      <div class="control-group">
        <label for="locationSelect">Dining Hall</label>
        <select id="locationSelect" onchange="loadMenu()">
          <option value="mcnutt-dining-hall">McNutt Dining Hall</option>
          <option value="forest-dining-hall">Forest Dining Hall</option>
          <option value="wright-eatery">Wright Eatery</option>
          <option value="collins-eatery">Collins Eatery</option>
          <option value="goodbody-hall-eatery">Goodbody Hall Eatery</option>
        </select>
      </div>

      <div class="control-group">
        <label for="stationFilter">Station Filter</label>
        <select id="stationFilter" onchange="applyFilters()">
          <option value="all">All Stations</option>
        </select>
      </div>

      <div class="control-group">
        <label for="searchInput">Search Food</label>
        <input 
          type="text" 
          id="searchInput" 
          placeholder="e.g. Chicken, Pizza, Salad..." 
          onkeyup="applyFilters()"
        />
      </div>
    </div>

    <!-- Main Dynamic Content Area -->
    <div id="menuContainer">
      <div class="loading-spinner">Loading menu data...</div>
    </div>
  </div>

  <!-- Sticky Tracker Bar at bottom -->
  <div id="trackerBar" class="tracker-bar">
    <div class="tracker-summary">
      <div><strong>Calories:</strong> <span id="totalCals">0</span> kcal</div>
      <div><strong>Protein:</strong> <span id="totalProtein">0</span>g</div>
      <div><strong>Carbs:</strong> <span id="totalCarbs">0</span>g</div>
      <div><strong>Fat:</strong> <span id="totalFat">0</span>g</div>
    </div>
    <button class="btn-toggle-log" onclick="toggleLogDrawer()">View Log (<span id="logCount">0</span>)</button>
  </div>

  <!-- Sliding Log Drawer -->
  <div id="logDrawer" class="log-drawer hidden">
    <div class="drawer-header">
      <h3>My Meal Log</h3>
      <button class="clear-btn" onclick="clearTracker()">Clear All</button>
    </div>
    <div id="logItemsContainer">
      <p class="empty-msg">No items added yet.</p>
    </div>
  </div>

  <script>
    let currentRawData = null;
    let mealLog = [];

    function initTracker() {
      const savedLog = localStorage.getItem('iu_meal_log');
      if (savedLog) {
        try {
          mealLog = JSON.parse(savedLog);
        } catch (e) {
          mealLog = [];
        }
      }
      updateTrackerUI();
      loadMenu();
    }

    async function loadMenu() {
      const location = document.getElementById('locationSelect').value;
      const container = document.getElementById('menuContainer');
      container.innerHTML = '<div class="loading-spinner">Loading menu data...</div>';

      try {
        const response = await fetch(`index.php?api_action=menu&loc=${location}`);
        const data = await response.json();

        if (data.error) {
          container.innerHTML = `<p style="text-align: center; color: red;">${data.message}</p>`;
          return;
        }

        currentRawData = data;
        populateStationDropdown(data);
        renderMenu(data);
      } catch (err) {
        container.innerHTML = '<p style="text-align: center; color: red;">Failed to load menu data.</p>';
      }
    }

    function populateStationDropdown(menuData) {
      const stationFilter = document.getElementById('stationFilter');
      stationFilter.innerHTML = '<option value="all">All Stations</option>';

      const items = menuData.days[0]?.menu_items || [];
      const stations = new Set();

      items.forEach(item => {
        const station = item.station || item.category || 'General';
        if (station) stations.add(station);
      });

      stations.forEach(station => {
        const opt = document.createElement('option');
        opt.value = station;
        opt.textContent = station;
        stationFilter.appendChild(opt);
      });
    }

    function getNutritionalInfo(item) {
      const foodObj = item.food || item.item || {};
      const info = foodObj.rounded_nutrition_info || item.rounded_nutrition_info || {};

      return {
        calories: parseInt(info.calories ?? item.calories ?? 0, 10),
        protein: parseInt(info.protein ?? 0, 10),
        carbs: parseInt(info.carbohydrates ?? 0, 10),
        fat: parseInt(info.total_fat ?? 0, 10)
      };
    }

    function renderMenu(menuData) {
      const container = document.getElementById('menuContainer');
      container.innerHTML = '';

      const items = menuData.days[0]?.menu_items || [];
      if (items.length === 0) {
        container.innerHTML = '<p style="text-align: center;">No menu items listed for this hall today.</p>';
        return;
      }

      // Group items by station while removing duplicates and zero-calorie items
      const grouped = {};
      
      items.forEach((item) => {
        const name = (item.text || (item.food && item.food.name) || 'Unknown Item').trim();
        const macros = getNutritionalInfo(item);

        // 1. FILTER: Omit zero-calorie items
        if (macros.calories <= 0) return;

        const station = item.station || item.category || 'General';
        if (!grouped[station]) grouped[station] = [];

        // 2. DEDUPLICATE: Check if item with this name already exists in station
        const alreadyExists = grouped[station].some(
          existing => (existing.text || existing.food?.name || '').trim().toLowerCase() === name.toLowerCase()
        );

        if (!alreadyExists) {
          grouped[station].push(item);
        }
      });

      let displayedTotal = 0;

      for (const [stationName, stationItems] of Object.entries(grouped)) {
        if (stationItems.length === 0) continue;
        displayedTotal += stationItems.length;

        const stationSection = document.createElement('div');
        stationSection.className = 'station-group';
        stationSection.setAttribute('data-station', stationName);

        let itemsHTML = stationItems.map(item => {
          const name = item.text || (item.food && item.food.name) || 'Unknown Item';
          const macros = getNutritionalInfo(item);

          return `
            <div class="food-card" data-name="${name.toLowerCase()}">
              <div>
                <h4>${name}</h4>
                <span class="calories-badge">${macros.calories} kcal</span>
              </div>
              <div class="macro-grid">
                <div class="macro-item"><span class="macro-label">Protein</span><span class="macro-value">${macros.protein}g</span></div>
                <div class="macro-item"><span class="macro-label">Carbs</span><span class="macro-value">${macros.carbs}g</span></div>
                <div class="macro-item"><span class="macro-label">Fat</span><span class="macro-value">${macros.fat}g</span></div>
              </div>
              <button class="add-btn" onclick="addToTracker('${encodeURIComponent(name)}', ${macros.calories}, ${macros.protein}, ${macros.carbs}, ${macros.fat})">+ Add to Meal</button>
            </div>
          `;
        }).join('');

        stationSection.innerHTML = `
          <h3 class="station-title">${stationName}</h3>
          <div class="food-grid">${itemsHTML}</div>
        `;

        container.appendChild(stationSection);
      }

      if (displayedTotal === 0) {
        container.innerHTML = '<p style="text-align: center;">No high-calorie menu items found for this location today.</p>';
      }
    }

    function addToTracker(nameEncoded, cals, protein, carbs, fat) {
      const name = decodeURIComponent(nameEncoded);
      mealLog.push({ name, cals, protein, carbs, fat });
      saveAndSyncUI();
    }

    function removeFromTracker(index) {
      mealLog.splice(index, 1);
      saveAndSyncUI();
    }

    function clearTracker() {
      if (confirm('Are you sure you want to clear your logged meal?')) {
        mealLog = [];
        saveAndSyncUI();
      }
    }

    function saveAndSyncUI() {
      localStorage.setItem('iu_meal_log', JSON.stringify(mealLog));
      updateTrackerUI();
    }

    function updateTrackerUI() {
      let totals = { cals: 0, protein: 0, carbs: 0, fat: 0 };

      const logContainer = document.getElementById('logItemsContainer');
      logContainer.innerHTML = '';

      if (mealLog.length === 0) {
        logContainer.innerHTML = '<p class="empty-msg">No items added yet.</p>';
      } else {
        mealLog.forEach((item, index) => {
          totals.cals += item.cals;
          totals.protein += item.protein;
          totals.carbs += item.carbs;
          totals.fat += item.fat;

          const div = document.createElement('div');
          div.className = 'log-item';
          div.innerHTML = `
            <div>
              <strong>${item.name}</strong><br>
              <small>${item.cals} kcal | P:${item.protein}g C:${item.carbs}g F:${item.fat}g</small>
            </div>
            <button class="remove-btn" onclick="removeFromTracker(${index})">✕</button>
          `;
          logContainer.appendChild(div);
        });
      }

      document.getElementById('totalCals').innerText = totals.cals;
      document.getElementById('totalProtein').innerText = totals.protein;
      document.getElementById('totalCarbs').innerText = totals.carbs;
      document.getElementById('totalFat').innerText = totals.fat;
      document.getElementById('logCount').innerText = mealLog.length;
    }

    function toggleLogDrawer() {
      document.getElementById('logDrawer').classList.toggle('hidden');
    }

    function applyFilters() {
      const searchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
      const selectedStation = document.getElementById('stationFilter').value;
      const stationGroups = document.querySelectorAll('.station-group');

      stationGroups.forEach(group => {
        const groupStation = group.getAttribute('data-station');
        const matchesStation = (selectedStation === 'all' || groupStation === selectedStation);
        let visibleCardsInGroup = 0;

        group.querySelectorAll('.food-card').forEach(card => {
          const foodName = card.getAttribute('data-name');
          if (matchesStation && foodName.includes(searchQuery)) {
            card.style.display = 'flex';
            visibleCardsInGroup++;
          } else {
            card.style.display = 'none';
          }
        });

        group.style.display = visibleCardsInGroup > 0 ? 'block' : 'none';
      });
    }

    document.addEventListener('DOMContentLoaded', initTracker);
  </script>
</body>
</html>