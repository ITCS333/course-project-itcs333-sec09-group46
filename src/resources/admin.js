/*
  Admin Resources Page
  - Uses src/resources/api/index.php
  - Admin can create, edit, delete resources
*/

const apiBase = 'api/index.php';

// --- Global State ---
let resources = [];
let editingId = null;

// --- Element Selections ---
const resourceForm       = document.querySelector('#resource-form');
const resourceIdInput    = document.querySelector('#resource-id');
const resourceTitleInput = document.querySelector('#resource-title');
const resourceDescInput  = document.querySelector('#resource-description');
const resourceLinkInput  = document.querySelector('#resource-link');
const submitButton       = document.querySelector('#submit-resource');
const cancelEditButton   = document.querySelector('#cancel-edit');
const resourcesTableBody = document.querySelector('#resources-tbody');

// --- Helpers ---

function createResourceRow(resource) {
  const tr = document.createElement('tr');

  const titleTd = document.createElement('td');
  titleTd.textContent = resource.title;

  const descTd = document.createElement('td');
  descTd.textContent = resource.description || '';

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

// --- API Calls ---

async function fetchResources() {
  const response = await fetch(apiBase);
  const json = await response.json();
  if (!json.success) {
    alert(json.message || 'Failed to load resources');
    return;
  }
  resources = json.data || [];
  renderTable();
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

// --- Event Handlers ---

async function handleAddResource(event) {
  event.preventDefault();

  const title = resourceTitleInput.value.trim();
  const description = resourceDescInput.value.trim();
  const link = resourceLinkInput.value.trim();

  if (!title || !link) {
    alert('Title and Link are required.');
    return;
  }

  const payload = { title, description, link };

  try {
    if (editingId === null) {
      // Create
      await createResource(payload);
    } else {
      // Update
      payload.id = editingId;
      await updateResource(payload);
    }

    await fetchResources(); // refresh list
    resetFormState();
  } catch (err) {
    alert(err.message);
  }
}

function handleTableClick(event) {
  const target = event.target;

  if (target.classList.contains('delete-btn')) {
    const id = target.dataset.id;
    if (!id) return;

    if (confirm('Are you sure you want to delete this resource?')) {
      deleteResource(id)
        .then(fetchResources)
        .catch((err) => alert(err.message));
    }
  }

  if (target.classList.contains('edit-btn')) {
    const id = target.dataset.id;
    if (!id) return;

    const resource = resources.find((r) => String(r.id) === String(id));
    if (!resource) {
      alert('Resource not found in local list.');
      return;
    }

    // Fill form for editing
    editingId = resource.id;
    resourceIdInput.value = resource.id;
    resourceTitleInput.value = resource.title;
    resourceDescInput.value = resource.description || '';
    resourceLinkInput.value = resource.link;

    submitButton.textContent = 'Save Changes';
    cancelEditButton.style.display = 'inline-block';
  }
}

function resetFormState() {
  editingId = null;
  resourceIdInput.value = '';
  resourceTitleInput.value = '';
  resourceDescInput.value = '';
  resourceLinkInput.value = '';
  submitButton.textContent = 'Add Resource';
  cancelEditButton.style.display = 'none';
}

// --- Initialization ---

async function loadAndInitialize() {
  // attach listeners
  resourceForm.addEventListener('submit', handleAddResource);
  resourcesTableBody.addEventListener('click', handleTableClick);
  cancelEditButton.addEventListener('click', resetFormState);

  // initial load
  await fetchResources();
}

loadAndInitialize();


// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
