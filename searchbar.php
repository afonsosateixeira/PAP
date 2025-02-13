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
                <select id="category-filter">
                    <option value="">Todas as categorias</option>
                    <!-- Categorias serão inseridas aqui -->
                </select>
            </div>
        </div>
    </div>
    
    <script>
        // Carregar categorias dinamicamente via PHP
        document.addEventListener('DOMContentLoaded', function() {
            fetch('category.php?action=get_categories') // Requisição para obter as categorias
                .then(response => response.json())
                .then(data => {
                    const categorySelect = document.getElementById('category-filter');
                    data.categories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        categorySelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Erro ao carregar categorias:', error));
        });

        // Exibir ou ocultar o dropdown de categorias
        document.getElementById('filter-btn').addEventListener('click', function (e) {
            e.stopPropagation(); // Impede o clique de propagar para o documento
            let dropdown = document.getElementById('filter-dropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        // Fechar o dropdown ao clicar fora dele
        document.addEventListener('click', function (e) {
            let dropdown = document.getElementById('filter-dropdown');
            let filterBtn = document.getElementById('filter-btn');
            if (!filterBtn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Fechar o dropdown quando a categoria for selecionada
        document.getElementById('category-filter').addEventListener('change', function() {
            let dropdown = document.getElementById('filter-dropdown');
            dropdown.style.display = 'none';  // Fecha o dropdown após a seleção de categoria
            filterNotes();
        });

        // Filtrando notas com base na categoria e no texto da pesquisa
        document.getElementById('search-input').addEventListener('input', function () {
            filterNotes();
        });

        function filterNotes() {
            let filterText = document.getElementById('search-input').value.toLowerCase();
            let filterCategory = document.getElementById('category-filter').value;
            let notes = document.querySelectorAll('.note-card');  // Seleciona todas as notas
            
            notes.forEach(note => {
                let title = note.querySelector('h3').textContent.toLowerCase();
                let category = note.getAttribute('data-category');  // Pega o ID da categoria da nota

                // Verifica se o título contém o texto de pesquisa e se a categoria é a selecionada
                if ((title.includes(filterText)) && (filterCategory === '' || category === filterCategory)) {
                    note.style.display = '';  // Exibe a nota
                } else {
                    note.style.display = 'none';  // Oculta a nota
                }
            });
        }

    </script>
</body>
</html>
