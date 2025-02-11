<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    .search-container {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        position: relative;
    }
    
    .search-bar {
        display: flex;
        align-items: center;
        width: 50%;
        border: 1px solid #ccc;
        border-radius: 5px;
        padding: 10px;
        background: #fff;
        position: relative;
    }
    
    .search-bar input {
        flex: 1;
        border: none;
        outline: none;
        font-size: 16px;
    }
    
    .search-bar i {
        color: #888;
        margin-right: 10px;
    }
    
    .filter-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 20px;
        color: white;
        position: absolute;
        right: 10px;
    }
    
    .filter-dropdown {
        display: none;
        position: absolute;
        top: 50px;
        right: 0;
        background: white;
        border: 1px solid #ccc;
        border-radius: 5px;
        padding: 10px;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        width: 100%;
    }
    
    .filter-dropdown select {
        width: calc(100% - 110px);
        padding: 5px;
        font-size: 16px;
    }
  </style>
</head>
<body>
    <div class="search-container">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" placeholder="Pesquisar notas...">
            <button class="filter-btn" id="filter-btn">
                <i class="fas fa-filter"></i>
            </button>
            <div class="filter-dropdown" id="filter-dropdown">
                <label for="category-filter">Escolha a categoria:</label>
                <select id="category-filter"></select>
                
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('search-input').addEventListener('input', function () {
            let filter = this.value.toLowerCase();
            let notes = document.querySelectorAll('.note-card');
    
            notes.forEach(note => {
                let title = note.querySelector('h3').textContent.toLowerCase();
                if (title.includes(filter)) {
                    note.style.display = '';
                } else {
                    note.style.display = 'none';
                }
            });
        });
        
        document.getElementById('filter-btn').addEventListener('click', function () {
            let dropdown = document.getElementById('filter-dropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });
    </script>
</body>
</html>