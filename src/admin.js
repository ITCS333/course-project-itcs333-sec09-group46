let resources = [];

const form = document.querySelector('#resource-form');
const titleInput = document.querySelector('#resource-title');
const descInput = document.querySelector('#resource-description');
const linkInput = document.querySelector('#resource-link');
const tableBody = document.querySelector('#resources-tbody');

function createRow(res) {
  const tr = document.createElement('tr');

  tr.innerHTML = `
    <td>${res.title}</td>
    <td>${res.description || ''}</td>
    <td><a href="${res.link}" target="_blank">Open</a></td>
    <td>
      <button class="delete-btn" data-id="${res.id}">Delete</button>
    </td>
  `;

  return tr;
}

function renderTable() {
  tableBody.innerHTML = '';
  resources.forEach(r => tableBody.appendChild(createRow(r)));
}

form.addEventListener('submit', e => {
  e.preventDefault();

  resources.push({
    id: `res_${Date.now()}`,
    title: titleInput.value,
    description: descInput.value,
    link: linkInput.value
  });

  form.reset();
  renderTable();
});

tableBody.addEventListener('click', e => {
  if (e.target.classList.contains('delete-btn')) {
    const id = e.target.dataset.id;
    resources = resources.filter(r => r.id !== id);
    renderTable();
  }
});

async function init() {
  const res = await fetch('resources.json');
  resources = await res.json();
  renderTable();
}

init();
