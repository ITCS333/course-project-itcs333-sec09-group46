const listSection = document.querySelector('#resource-list-section');

function createResourceArticle(resource) {
  const article = document.createElement('article');

  article.innerHTML = `
    <h3>${resource.title}</h3>
    <p>${resource.description || ''}</p>
    <a href="details.html?id=${resource.id}">
      View Resource & Discussion
    </a>
  `;

  return article;
}

async function loadResources() {
  const response = await fetch('resources.json');
  const resources = await response.json();

  listSection.innerHTML = '';

  resources.forEach(res => {
    listSection.appendChild(createResourceArticle(res));
  });
}

loadResources();
