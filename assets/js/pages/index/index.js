let notes = [];
let editandoId = null;

function carregarNotes() {
    fetch('notes.php')
        .then(response => response.json())
        .then(data => {
            notes = data;
            mostrarNotes();
        });
}

function mostrarNotes() {
    const container = document.getElementById('notesContainer');
    container.innerHTML = '';
    notes.forEach(note => {
        const div = document.createElement('div');
        div.className = 'note';
        div.onclick = () => abrirEditar(note.id);
        div.innerHTML = `
            <h3>${note.titulo}</h3>
            <p>${note.conteudo}</p>
            <div class="menu">
                <button onclick="event.stopPropagation(); deletarNota(${note.id})">Deletar</button>
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
    const note = notes.find(n => n.id == id);
    if (!note) return;
    editandoId = id;
    document.getElementById('modalTitle').textContent = 'Editar Nota';
    document.getElementById('titulo').value = note.titulo;
    document.getElementById('conteudo').value = note.conteudo;
    document.getElementById('modal').style.display = 'block';
    document.getElementById('modal-overlay').style.display = 'block';
}

function salvarNota() {
    const titulo = document.getElementById('titulo').value;
    const conteudo = document.getElementById('conteudo').value;

    const acao = editandoId ? 'editar' : 'criar';
    fetch('notes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ titulo, conteudo, acao, id: editandoId || '' })
    }).then(() => {
        carregarNotes();
        fecharModal();
    });
}

function deletarNota(id) {
    fetch('notes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ acao: 'deletar', id })
    }).then(() => carregarNotes());
}

function fecharModal() {
    document.getElementById('modal').style.display = 'none';
    document.getElementById('modal-overlay').style.display = 'none';
}

carregarNotes();
