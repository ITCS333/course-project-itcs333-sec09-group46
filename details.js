const resourceTitle = document.querySelector('#resource-title');
const resourceDescription = document.querySelector('#resource-description');
const resourceLink = document.querySelector('#resource-link');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newComment = document.querySelector('#new-comment');

let currentResourceId = null;
let currentComments = [];

function getIdFromURL() {
  return new URLSearchParams(window.location.search).get('id');
}

function renderResource(resource) {
  resourceTitle.textContent = resource.title;
  resourceDescription.textContent = resource.description || '';
  resourceLink.href = resource.link;
}

function renderComments() {
  commentList.innerHTML = '';

  if (currentComments.length === 0) {
    commentList.innerHTML = '<p>No comments yet.</p>';
    return;
  }

  currentComments.forEach(c => {
    const article = document.createElement('article');
    article.innerHTML = `
      <p>${c.text}</p>
      <footer>Posted by: ${c.author}</footer>
    `;
    commentList.appendChild(article);
  });
}

async function init() {
  currentResourceId = getIdFromURL();
  if (!currentResourceId) return;

  const [resRes, resCom] = await Promise.all([
    fetch('resources.json'),
    fetch('comments.json')
  ]);

  const resources = await resRes.json();
  const comments = await resCom.json();

  const resource = resources.find(r => r.id === currentResourceId);
  if (resource) renderResource(resource);

  currentComments = comments[currentResourceId] || [];
  renderComments();

  commentForm.addEventListener('submit', e => {
    e.preventDefault();
    if (!newComment.value.trim()) return;

    currentComments.push({
      author: 'Student',
      text: newComment.value
    });

    newComment.value = '';
    renderComments();
  });
}

init();
