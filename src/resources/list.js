

const apiBase = '/task2/api/index.php';
const detailsBase = '/task2/details.html';

const section = document.querySelector('#resource-list-section');


function createResourceCard(resource) {
  const col = document.createElement('div');
  col.className = 'col-12 col-md-6 col-lg-4';

  const card = document.createElement('div');
  card.className = 'card h-100 shadow-sm';

  // Thumbnail / placeholder
  if (resource.thumbnail && resource.thumbnail.trim() !== '') {
    const img = document.createElement('img');
    img.className = 'card-img-top';
    img.src = resource.thumbnail;
    img.alt = resource.title || 'Course thumbnail';
    img.style.height = '160px';
    img.style.objectFit = 'cover';
    card.appendChild(img);
  } else {
    const placeholder = document.createElement('div');
    placeholder.className =
      'card-img-top bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center';
    placeholder.style.height = '160px';
    placeholder.textContent = 'No image';
    placeholder.style.fontWeight = '600';
    card.appendChild(placeholder);
  }

  const body = document.createElement('div');
  body.className = 'card-body d-flex flex-column';

  const titleEl = document.createElement('h5');
  titleEl.className = 'card-title';
  titleEl.textContent = resource.title;

  const descEl = document.createElement('p');
  descEl.className = 'card-text small text-muted flex-grow-1';
  descEl.textContent = resource.description || '';

  const btnRow = document.createElement('div');
  btnRow.className = 'd-flex justify-content-between mt-2';

  const viewBtn = document.createElement('a');
  viewBtn.className = 'btn btn-outline-primary btn-sm';
  viewBtn.href = `${detailsBase}?id=${encodeURIComponent(resource.id)}`;
  viewBtn.textContent = 'View';

  btnRow.appendChild(viewBtn);

  body.appendChild(titleEl);
  body.appendChild(descEl);
  body.appendChild(btnRow);

  card.appendChild(body);
  col.appendChild(card);

  return col;
}

function createResourceArticle(resource) {
  return createResourceCard(resource);
}

function renderResources(resources) {
  if (!section) return;

  section.innerHTML = '';

  const row = document.createElement('div');
  row.className = 'row g-4';

  if (!resources || resources.length === 0) {
    const p = document.createElement('p');
    p.className = 'text-muted';
    p.textContent = 'No course resources available yet.';
    section.appendChild(p);
    return;
  }

  resources.forEach((res) => {
    // You can use either function; both work.
    // Using createResourceArticle keeps it aligned with the task name.
    const cardCol = createResourceArticle(res);
    row.appendChild(cardCol);
  });

  section.appendChild(row);
}

async function loadResources() {
  if (!section) return;

  section.innerHTML = '<p class="text-muted">Loading resources…</p>';

  try {
    const response = await fetch(apiBase);
    const json = await response.json();

    if (!json.success) {
      throw new Error(json.message || 'Error loading resources.');
    }

    renderResources(json.data || []);
  } catch (err) {
    console.error('Error loading resources', err);
    section.innerHTML = `
      <div class="alert alert-danger">
        Error loading resources.
      </div>
    `;
  }
}

// Initial load
loadResources();
