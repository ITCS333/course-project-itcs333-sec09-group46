/*
  Resource Details + Discussion
  - Loads a single resource by ?id=...
  - Loads comments for that resource
  - Allows posting a new comment
*/

const apiBase = 'api/index.php';

// --- Global state ---
let currentResourceId = null;
let currentComments = [];

// --- Element selections ---
const resourceTitle       = document.querySelector('#resource-title');
const resourceDescription = document.querySelector('#resource-description');
const resourceLink        = document.querySelector('#resource-link');
const commentList         = document.querySelector('#comment-list');
const commentForm         = document.querySelector('#comment-form');
const newComment          = document.querySelector('#new-comment');

// --- Functions ---

function getResourceIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

function renderResourceDetails(resource) {
  resourceTitle.textContent = resource.title;
  resourceDescription.textContent = resource.description || '';
  resourceLink.href = resource.link;
}

function createCommentArticle(comment) {
  const article = document.createElement('article');

  const textP = document.createElement('p');
  textP.textContent = comment.text;

  const footer = document.createElement('footer');
  const author = comment.author || 'Unknown';
  const date   = comment.created_at
    ? new Date(comment.created_at).toLocaleString()
    : '';

  footer.textContent = `Posted by: ${author}` + (date ? ` on ${date}` : '');

  article.appendChild(textP);
  article.appendChild(footer);

  return article;
}

function renderComments() {
  commentList.innerHTML = '';

  if (currentComments.length === 0) {
    const p = document.createElement('p');
    p.textContent = 'No comments yet. Be the first to comment!';
    commentList.appendChild(p);
    return;
  }

  currentComments.forEach((c) => {
    const article = createCommentArticle(c);
    commentList.appendChild(article);
  });
}

async function loadResourceAndComments() {
  const id = getResourceIdFromURL();
  currentResourceId = id;

  if (!id) {
    resourceTitle.textContent = 'Resource not found.';
    resourceDescription.textContent = '';
    resourceLink.href = '#';
    commentList.textContent = '';
    return;
  }

  try {
    // Fetch resource and comments in parallel
    const [resResource, resComments] = await Promise.all([
      fetch(`${apiBase}?id=${encodeURIComponent(id)}`),
      fetch(`${apiBase}?action=comments&resource_id=${encodeURIComponent(id)}`)
    ]);

    const jsonResource  = await resResource.json();
    const jsonComments  = await resComments.json();

    if (!jsonResource.success) {
      resourceTitle.textContent = jsonResource.message || 'Resource not found.';
      resourceDescription.textContent = '';
      resourceLink.href = '#';
    } else {
      renderResourceDetails(jsonResource.data);
    }

    if (!jsonComments.success) {
      commentList.textContent = jsonComments.message || 'Failed to load comments.';
    } else {
      currentComments = jsonComments.data || [];
      renderComments();
    }
  } catch (err) {
    console.error(err);
    resourceTitle.textContent = 'Error loading resource.';
    commentList.textContent = 'Error loading comments.';
  }
}

async function handleAddComment(event) {
  event.preventDefault();

  const text = newComment.value.trim();
  if (!text) return;

  if (!currentResourceId) {
    alert('No resource selected.');
    return;
  }

  try:
    const response = await fetch(`${apiBase}?action=comment`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        resource_id: currentResourceId,
        text
      })
    });
    const json = await response.json();

    if (!json.success) {
      alert(json.message || 'Failed to add comment');
      return;
    }

    // Reload comments from server
    await loadCommentsOnly();

    newComment.value = '';
  } catch (err) {
    console.error(err);
    alert('Error adding comment.');
  }
}

async function loadCommentsOnly() {
  if (!currentResourceId) return;

  try {
    const res = await fetch(
      `${apiBase}?action=comments&resource_id=${encodeURIComponent(currentResourceId)}`
    );
    const json = await res.json();

    if (!json.success) {
      commentList.textContent = json.message || 'Failed to load comments.';
      return;
    }

    currentComments = json.data || [];
    renderComments();
  } catch (err) {
    console.error(err);
    commentList.textContent = 'Error loading comments.';
  }
}

// --- Initialization ---
async function initializePage() {
  await loadResourceAndComments();
  commentForm.addEventListener('submit', handleAddComment);
}

initializePage();
