/*
  Course Resources List (Student View)
  - Read-only list of resources
  - Each links to details.html?id=...
*/

const apiBase = 'api/index.php';

const listSection = document.querySelector('#resource-list-section');

function createResourceArticle(resource) {
  const article = document.createElement('article');

  const titleEl = document.createElement('h3');
  titleEl.textContent = resource.title;

  const descEl = document.createElement('p');
  descEl.textContent = resource.description || '';

  const detailsLink = document.createElement('a');
  detailsLink.href = `details.html?id=${encodeURIComponent(resource.id)}`;
  detailsLink.textContent = 'View Resource & Discussion';

  article.appendChild(titleEl);
  article.appendChild(descEl);
  article.appendChild(detailsLink);

  return article;
}

async function loadResources() {
  try {
    const response = await fetch(apiBase);
    const json = await response.json();

    if (!json.success) {
      listSection.textContent = json.message || 'Failed to load resources.';
      return;
    }

    const resources = json.data || [];

    listSection.innerHTML = '';

    if (resources.length === 0) {
      const p = document.createElement('p');
      p.textContent = 'No resources available yet.';
      listSection.appendChild(p);
      return;
    }

    resources.forEach((res) => {
      const article = createResourceArticle(res);
      listSection.appendChild(article);
    });
  } catch (err) {
    listSection.textContent = 'Error loading resources.';
    console.error(err);
  }
}

// Initial load
loadResources();
