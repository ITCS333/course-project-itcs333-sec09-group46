/*
  Admin Resources Page
  - Uses /task2/api/index.php
  - Admin can create, edit, delete resources
  - Also shows "Current courses" as cards (like student view),
    but "View" goes to detail.php so admin can see comments.
*/

const apiBase = '/task2/api/index.php';

// --- Global State ---
let resources = [];
let editingId = null;

// --- Element Selections ---
const resourceForm       = document.querySelector('#resource-form');
const resourceIdInput    = document.querySelector('#resource-id');
const resourceTitleInput = document.querySelector('#resource-title');
const resourceDescInput  = document.querySelector('#resource-description');
const resourceLinkInput  = document.querySelector('#resource-link');
const thumbnailInput     = document.querySelector('#resource-thumbnail');
const submitButton       = document.querySelector('#submit-resource');
const cancelEditButton   = document.querySelector('#cancel-edit');
const resourcesTableBody = document.querySelector('#resources-tbody');

// Container for the card view at top (Current course resources)
const cardsContainer     = document.querySelector('#admin-course-cards');

/* ------------------------------------------------------------------
   Card view (Current courses)
   ------------------------------------------------------------------ */

function createResourceCard(resource) {
  // column
  const col = document.createElement('div');
  col.className = 'col-12 col-md-6 col-lg-4';

  // card
  const card = document.createElement('div');
  card.className = 'card h-100 shadow-sm';

  // image / placeholder
  if (resource.thumbnail && resource.thumbnail.trim() !== '') {
    const img = document.createElement('img');
    img.className = 'card-img-top';
    img.src = resource.thumbnail;
    img.alt = resource.title || 'Course thumbnail';
    img.style.height = '160px';
    img.style.objectFit = 'cover';
    card.appendChild(img);
  } else {
    const pic = document.createElement('div');
    pic.className =
      'card-img-top bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center';
    pic.style.height = '160px';
    pic.textContent = 'No image';
    pic.style.fontWeight = '600';
    card.appendChild(pic);
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
  btnRow.className = 'd-flex justify-content-start mt-2';

  // ONLY a View button → go to detail.php (comments page)
  const viewBtn = document.createElement('a');
  viewBtn.className = 'btn btn-outline-primary btn-sm';
  viewBtn.href = '/task2/details.html?id=' + encodeURIComponent(resource.id);
  viewBtn.textContent = 'View';

  btnRow.appendChild(viewBtn);

  body.appendChild(titleEl);
  body.appendChild(descEl);
  body.appendChild(btnRow);

  card.appendChild(body);
  col.appendChild(card);

  return col;
}


function renderCards() {
  if (!cardsContainer) return;

  cardsContainer.innerHTML = '';

  if (!resources || resources.length === 0) {
    const p = document.createElement('p');
    p.className = 'text-muted';
    p.textContent = 'No courses available yet.';
    cardsContainer.appendChild(p);
    return;
  }

  resources.forEach((res) => {
    const cardCol = createResourceCard(res);
    cardsContainer.appendChild(cardCol);
  });
}

/* ------------------------------------------------------------------
   Table view (Existing Resources)
   ------------------------------------------------------------------ */

function createResourceRow(resource) {
  const tr = document.createElement('tr');

  const titleTd = document.createElement('td');
  titleTd.textContent = resource.title;

  const descTd = document.createElement('td');
  descTd.textContent = resource.description || '';

  const thumbTd = document.createElement('td');
  if (resource.thumbnail && resource.thumbnail.trim() !== '') {
    const thumbLink = document.createElement('a');
    thumbLink.href = resource.thumbnail;
    thumbLink.textContent = 'Open';
    thumbLink.target = '_blank';
    thumbTd.appendChild(thumbLink);
  } else {
    thumbTd.textContent = '-';
  }

  const linkTd = document.createElement('td');
  const linkA = document.createElement('a');
  linkA.href = resource.link;
  linkA.textContent = 'Open';
  linkA.target = '_blank';
  linkTd.appendChild(linkA);

  const actionsTd = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.classList.add('edit-btn');
  editBtn.dataset.id = resource.id;

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-btn');
  deleteBtn.dataset.id = resource.id;

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(titleTd);
  tr.appendChild(descTd);
  tr.appendChild(thumbTd);
  tr.appendChild(linkTd);
  tr.appendChild(actionsTd);

  return tr;
}

function renderTable() {
  resourcesTableBody.innerHTML = '';

  resources.forEach((res) => {
    const row = createResourceRow(res);
    resourcesTableBody.appendChild(row);
  });
}

/* ------------------------------------------------------------------
   API Calls
   ------------------------------------------------------------------ */

async function fetchResources() {
  const response = await fetch(apiBase);
  const json = await response.json();
  if (!json.success) {
    alert(json.message || 'Failed to load resources');
    return;
  }
  resources = json.data || [];
  renderTable();
  renderCards();  // update cards too
}

async function createResource(data) {
  const response = await fetch(apiBase, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
  const json = await response.json();
  if (!json.success) {
    throw new Error(json.message || 'Failed to create resource');
  }
}

async function updateResource(data) {
  const response = await fetch(apiBase, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
  const json = await response.json();
  if (!json.success) {
    throw new Error(json.message || 'Failed to update resource');
  }
}

async function deleteResource(id) {
  const response = await fetch(`${apiBase}?id=${encodeURIComponent(id)}`, {
    method: 'DELETE',
  });
  const json = await response.json();
  if (!json.success) {
    throw new Error(json.message || 'Failed to delete resource');
  }
}

/* ------------------------------------------------------------------
   Event Handlers
   ------------------------------------------------------------------ */

async function handleAddResource(event) {
  event.preventDefault();

  const title       = resourceTitleInput.value.trim();
  const description = resourceDescInput.value.trim();
  const link        = resourceLinkInput.value.trim();
  const thumbnail   = thumbnailInput.value.trim() || null;

  if (!title || !link) {
    alert('Title and Link are required.');
    return;
  }

  const payload = { title, description, link, thumbnail };

  try {
    if (editingId === null) {
      await createResource(payload);
    } else {
      payload.id = editingId;
      await updateResource(payload);
    }

    await fetchResources();
    resetFormState();
  } catch (err) {
    alert(err.message);
  }
}

function handleTableClick(event) {
  const target = event.target;

  // Delete
  if (target.classList.contains('delete-btn')) {
    const id = target.dataset.id;
    if (!id) return;

    if (confirm('Are you sure you want to delete this resource?')) {
      deleteResource(id)
        .then(fetchResources)
        .catch((err) => alert(err.message));
    }
  }

  // Edit (from table OR from card edit button, same class & dataset)
  if (target.classList.contains('edit-btn')) {
    const id = target.dataset.id;
    if (!id) return;

    const resource = resources.find((r) => String(r.id) === String(id));
    if (!resource) {
      alert('Resource not found in local list.');
      return;
    }

    editingId = resource.id;
    resourceIdInput.value    = resource.id;
    resourceTitleInput.value = resource.title;
    resourceDescInput.value  = resource.description || '';
    resourceLinkInput.value  = resource.link;
    thumbnailInput.value     = resource.thumbnail || '';

    submitButton.textContent       = 'Save Changes';
    cancelEditButton.style.display = 'inline-block';
  }
}

function resetFormState() {
  editingId = null;
  resourceIdInput.value    = '';
  resourceTitleInput.value = '';
  resourceDescInput.value  = '';
  resourceLinkInput.value  = '';
  thumbnailInput.value     = '';
  submitButton.textContent = 'Add Resource';
  cancelEditButton.style.display = 'none';
}

/* ------------------------------------------------------------------
   Initialization
   ------------------------------------------------------------------ */

async function loadAndInitialize() {
  resourceForm.addEventListener('submit', handleAddResource);
  resourcesTableBody.addEventListener('click', handleTableClick);

  // cards also use .edit-btn, so we delegate click on whole document
  document.addEventListener('click', (e) => {
    if (e.target.classList && e.target.classList.contains('edit-btn')) {
      handleTableClick(e);
    }
  });

  cancelEditButton.addEventListener('click', resetFormState);

  await fetchResources();
}

// Initial Page Load
loadAndInitialize();
oadAndInitialize();
