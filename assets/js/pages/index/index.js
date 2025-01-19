let notas = [];
let editandoId = null;

function carregarNotas() {
    fetch('notas.php')
        .then(response => response.json())
        .then(data => {
            notas = data;
            mostrarNotas();
        });
}

function mostrarNotas() {
    const container = document.getElementById('notasContainer');
    container.innerHTML = '';
    notas.forEach(nota => {
        const div = document.createElement('div');
        div.className = 'nota';
        div.onclick = () => abrirEditar(nota.id);
        div.innerHTML = `
            <h3>${nota.titulo}</h3>
            <p>${nota.conteudo}</p>
            <div class="menu">
                <button onclick="event.stopPropagation(); deletarNota(${nota.id})">Deletar</button>
            </div>
        `;
        container.appendChild(div);
    });
}

function criarNota() {
    editandoId = null;
    document.getElementById('modalTitle').textContent = 'Nova Nota';
    document.getElementById('titulo').value = '';
    document.getElementById('conteudo').value = '';
    document.getElementById('modal').style.display = 'block';
    document.getElementById('modal-overlay').style.display = 'block';
}

function abrirEditar(id) {
    const nota = notas.find(n => n.id == id);
    if (!nota) return;
    editandoId = id;
    document.getElementById('modalTitle').textContent = 'Editar Nota';
    document.getElementById('titulo').value = nota.titulo;
    document.getElementById('conteudo').value = nota.conteudo;
    document.getElementById('modal').style.display = 'block';
    document.getElementById('modal-overlay').style.display = 'block';
}

function salvarNota() {
    const titulo = document.getElementById('titulo').value;
    const conteudo = document.getElementById('conteudo').value;

    const acao = editandoId ? 'editar' : 'criar';
    fetch('notas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ titulo, conteudo, acao, id: editandoId || '' })
    }).then(() => {
        carregarNotas();
        fecharModal();
    });
}

function deletarNota(id) {
    fetch('notas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ acao: 'deletar', id })
    }).then(() => carregarNotas());
}

function fecharModal() {
    document.getElementById('modal').style.display = 'none';
    document.getElementById('modal-overlay').style.display = 'none';
}

carregarNotas();