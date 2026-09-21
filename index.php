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
  <title>IU Dining Hall Calorie Tracker</title>
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

    /* Controls Bar Styling */
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

    /* Layout & Station Cards */
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
      margin: 0 0 0.5rem 0;
      font-size: 1.05rem;
    }

    .calories-badge {
      display: inline-block;
      background: var(--iu-cream);
      color: var(--text-dark);
      font-weight: 600;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      font-size: 0.85rem;
      align-self: flex-start;
    }

    .loading-spinner {
      text-align: center;
      padding: 3rem;
      color: var(--text-muted);
      font-size: 1.1rem;
    }
  </style>
</head>
<body>

  <header>
    <h1>IU Dining Hall Calorie Tracker</h1>
  </header>

  <div class="container">
    <!-- Controls Layout -->
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

 <script>
    let currentRawData = null;

    // Fetch JSON menu data for the selected location
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
        container.innerHTML = '<p style="text-align: center; color: red;">Failed to load menu data. Ensure synchronization script has run.</p>';
      }
    }

    // Extract station categories from Nutrislice JSON and populate dropdown
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

    // Safely extract macronutrient info from item structure
    function getNutritionalInfo(item) {
      const foodObj = item.food || item.item || {};
      const info = foodObj.rounded_nutrition_info || item.rounded_nutrition_info || {};

      return {
        calories: info.calories ?? item.calories ?? 'N/A',
        protein: info.protein ? `${info.protein}g` : '--',
        carbs: info.carbohydrates ? `${info.carbohydrates}g` : '--',
        fat: info.total_fat ? `${info.total_fat}g` : '--'
      };
    }

    // Render grouped station cards
    function renderMenu(menuData) {
      const container = document.getElementById('menuContainer');
      container.innerHTML = '';

      const items = menuData.days[0]?.menu_items || [];
      if (items.length === 0) {
        container.innerHTML = '<p style="text-align: center;">No menu items listed for this hall today.</p>';
        return;
      }

      // Group items by station name
      const grouped = {};
      items.forEach(item => {
        const station = item.station || item.category || 'General';
        if (!grouped[station]) grouped[station] = [];
        grouped[station].push(item);
      });

      // Generate HTML for each station group
      for (const [stationName, stationItems] of Object.entries(grouped)) {
        const stationSection = document.createElement('div');
        stationSection.className = 'station-group';
        stationSection.setAttribute('data-station', stationName);

        let itemsHTML = stationItems.map(item => {
          const name = item.text || (item.food && item.food.name) || 'Unknown Item';
          const macros = getNutritionalInfo(item);
          const calDisplay = macros.calories !== 'N/A' ? `${macros.calories} kcal` : 'N/A';

          return `
            <div class="food-card" data-name="${name.toLowerCase()}">
              <div>
                <h4>${name}</h4>
                <span class="calories-badge">${calDisplay}</span>
              </div>
              <div class="macro-grid">
                <div class="macro-item">
                  <span class="macro-label">Protein</span>
                  <span class="macro-value">${macros.protein}</span>
                </div>
                <div class="macro-item">
                  <span class="macro-label">Carbs</span>
                  <span class="macro-value">${macros.carbs}</span>
                </div>
                <div class="macro-item">
                  <span class="macro-label">Fat</span>
                  <span class="macro-value">${macros.fat}</span>
                </div>
              </div>
            </div>
          `;
        }).join('');

        stationSection.innerHTML = `
          <h3 class="station-title">${stationName}</h3>
          <div class="food-grid">${itemsHTML}</div>
        `;

        container.appendChild(stationSection);
      }
    }

    // Apply Real-Time Search & Station Dropdown Filtering
    function applyFilters() {
      const searchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
      const selectedStation = document.getElementById('stationFilter').value;

      const stationGroups = document.querySelectorAll('.station-group');

      stationGroups.forEach(group => {
        const groupStation = group.getAttribute('data-station');
        const matchesStation = (selectedStation === 'all' || groupStation === selectedStation);

        let visibleCardsInGroup = 0;
        const cards = group.querySelectorAll('.food-card');

        cards.forEach(card => {
          const foodName = card.getAttribute('data-name');
          const matchesSearch = foodName.includes(searchQuery);

          if (matchesStation && matchesSearch) {
            card.style.display = 'flex';
            visibleCardsInGroup++;
          } else {
            card.style.display = 'none';
          }
        });

        // Hide empty station headers when filtering
        group.style.display = visibleCardsInGroup > 0 ? 'block' : 'none';
      });
    }

    // Initial load on page startup
    document.addEventListener('DOMContentLoaded', loadMenu);
  </script>
</body>
</html>