import { readFileSync, writeFileSync, mkdirSync, existsSync, cpSync, readdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, '..');
const outRoot = join(root, 'public_html');

const partials = {
  head: readFileSync(join(__dirname, 'partials/head.html'), 'utf8'),
  header: readFileSync(join(__dirname, 'partials/header.html'), 'utf8'),
  footer: readFileSync(join(__dirname, 'partials/footer.html'), 'utf8'),
};

const content = JSON.parse(
  readFileSync(join(__dirname, 'data/content.json'), 'utf8')
);

const SITE_TITLE =
  'Positive Tree Foundation - Where comfort, care, and community come together';

function readPage(name) {
  return readFileSync(join(__dirname, 'pages', name), 'utf8');
}

function replaceAll(str, vars) {
  let out = str;
  for (const [key, val] of Object.entries(vars)) {
    out = out.split(`{{${key}}}`).join(val ?? '');
  }
  return out;
}

function activeClass(isActive) {
  return isActive ? ' class="active"' : '';
}

function buildNavVars(pageKey) {
  return {
    activeHome: activeClass(pageKey === 'home'),
    activeAboutFoundation: activeClass(pageKey === 'about-foundation'),
    activeAboutUs: activeClass(pageKey === 'about-us'),
    activeProgramsElderly: activeClass(pageKey === 'programs-for-elderly'),
    activeProgramsChildren: activeClass(pageKey === 'programs-for-children'),
    activeGallery: activeClass(pageKey === 'gallery'),
    activeNews: activeClass(pageKey === 'news-events'),
    activeContact: activeClass(pageKey === 'contact'),
    activeDonate: activeClass(pageKey === 'donate'),
  };
}

function renderPage({
  body,
  title,
  description = 'Positive Tree Foundation — Where comfort, care, and community come together',
  pageKey = '',
  extraHead = '',
  extraScripts = '',
}) {
  const vars = {
    title: title || SITE_TITLE,
    description,
    extraHead,
    extraScripts,
    ...buildNavVars(pageKey),
  };

  return `<!DOCTYPE html>
<html lang="en">
<head>
${replaceAll(partials.head, vars)}
</head>
<body>
${replaceAll(partials.header, vars)}
<main>
${body}
</main>
${replaceAll(partials.footer, vars)}
</body>
</html>
`;
}

function copyMarketingScripts() {
  const jsDir = join(__dirname, 'js');
  const outJsDir = join(outRoot, 'js');
  mkdirSync(outJsDir, { recursive: true });

  if (!existsSync(jsDir)) {
    return;
  }

  for (const file of readdirSync(jsDir)) {
    if (!file.endsWith('.js')) continue;
    cpSync(join(jsDir, file), join(outJsDir, file));
    console.log(`  copied js/${file}`);
  }
}

function writeOutput(relativePath, html) {
  const fullPath = join(outRoot, relativePath);
  mkdirSync(dirname(fullPath), { recursive: true });
  writeFileSync(fullPath, html, 'utf8');
  console.log(`  wrote ${relativePath}`);
}

function formatDate(dateStr) {
  const d = new Date(dateStr.replace(' ', 'T') + 'Z');
  return d.toLocaleDateString('en-IN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}

function excerptFromHtml(html, maxLen = 160) {
  const text = html
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
  if (text.length <= maxLen) return text;
  return text.slice(0, maxLen).trim() + '…';
}

function postUrl(post, basePath) {
  return `${basePath}${post.slug}/`;
}

function renderPostCard(post, basePath) {
  const url = postUrl(post, basePath);
  const excerpt = post.excerpt || excerptFromHtml(post.html);
  const alt = post.title.replace(/"/g, '&quot;');
  const image = post.image || '/images/2023/07/about.png';
  return `<article class="post-card">
  <a href="${url}" class="post-card__image" aria-hidden="true" tabindex="-1">
    <img src="${image}" alt="${alt}" loading="lazy" />
  </a>
  <div class="post-card__body">
    <p class="post-card__date">${formatDate(post.date)}</p>
    <h3 class="post-card__title"><a href="${url}">${post.title}</a></h3>
    <p class="post-card__excerpt">${excerpt}</p>
    <a href="${url}" class="post-card__link">Read more →</a>
  </div>
</article>`;
}

function renderPostGrid(posts, basePath) {
  if (!posts.length) {
    return '<p class="text-muted">No posts found.</p>';
  }
  return `<div class="post-grid">${posts.map((p) => renderPostCard(p, basePath)).join('\n')}</div>`;
}

function sanitizePostHtml(html) {
  if (!html) return '<p>Content coming soon.</p>';
  return html
    .replace(/https:\/\/positivetree\.ngo\/wp-content\/uploads\//g, '/images/')
    .replace(/https:\/\/positivetree\.ngo\/wp-payment\/service\.php/g, '/donate/')
    .replace(/https:\/\/positivetree\.ngo\//g, '/');
}

const staticPages = [
  {
    out: 'index.html',
    page: 'index.html',
    pageKey: 'home',
    title: SITE_TITLE,
    extraScripts: '  <script src="/js/hero-slider.js"></script>\n',
  },
  {
    out: 'about-foundation/index.html',
    page: 'about-foundation.html',
    pageKey: 'about-foundation',
    title: `About Foundation | ${SITE_TITLE}`,
  },
  {
    out: 'about-us/index.html',
    page: 'about-us.html',
    pageKey: 'about-us',
    title: `About Founder | ${SITE_TITLE}`,
  },
  {
    out: 'contact/index.html',
    page: 'contact.html',
    pageKey: 'contact',
    title: `Contact | ${SITE_TITLE}`,
  },
  {
    out: 'gallery/index.html',
    page: 'gallery.html',
    pageKey: 'gallery',
    title: `Gallery | ${SITE_TITLE}`,
  },
  {
    out: 'donate/index.html',
    page: 'donate.html',
    pageKey: 'donate',
    title: `Donate | ${SITE_TITLE}`,
    extraScripts: '  <script src="/js/donate-checkout.js"></script>\n',
  },
];

console.log('Building marketing site…');
copyMarketingScripts();

for (const cfg of staticPages) {
  const body = readPage(cfg.page);
  writeOutput(
    cfg.out,
    renderPage({
      body,
      title: cfg.title,
      pageKey: cfg.pageKey,
      extraScripts: cfg.extraScripts || '',
    })
  );
}

// News & Events — posts in "events" category
const eventPosts = content.posts.filter((p) =>
  p.categories.some((c) => c.slug === 'events')
);
const newsBody = replaceAll(readPage('news-events.html'), {
  POST_GRID: renderPostGrid(eventPosts, '/news-events/'),
});
writeOutput(
  'news-events/index.html',
  renderPage({
    body: newsBody,
    title: `News & Events | ${SITE_TITLE}`,
    pageKey: 'news-events',
  })
);

// Category archives
const categoryConfigs = [
  {
    slug: 'programs-for-children',
    pageKey: 'programs-for-children',
    label: 'Programs for Children',
    title: 'Programs With Children',
  },
  {
    slug: 'programs-for-elderly',
    pageKey: 'programs-for-elderly',
    label: 'Initiatives For Youth',
    title: 'Programs With Youth',
  },
];

for (const cat of categoryConfigs) {
  const posts = content.posts.filter((p) =>
    p.categories.some((c) => c.slug === cat.slug)
  );
  const body = replaceAll(readPage('category-archive.html'), {
    CATEGORY_LABEL: cat.label,
    CATEGORY_TITLE: cat.title,
    POST_GRID: renderPostGrid(posts, `/category/${cat.slug}/`),
  });
  writeOutput(
    `category/${cat.slug}/index.html`,
    renderPage({
      body,
      title: `${cat.title} | ${SITE_TITLE}`,
      pageKey: cat.pageKey,
    })
  );

  // Individual post pages
  const template = readPage('post-single.html');
  for (const post of posts) {
    const postBody = replaceAll(template, {
      POST_TITLE: post.title,
      POST_DATE: formatDate(post.date),
      POST_BODY: sanitizePostHtml(post.html),
      BACK_URL: `/category/${cat.slug}/`,
    });
    writeOutput(
      `category/${cat.slug}/${post.slug}/index.html`,
      renderPage({
        body: postBody,
        title: `${post.title} | ${SITE_TITLE}`,
        pageKey: cat.pageKey,
      })
    );
  }
}

// Event post individual pages under news-events
for (const post of eventPosts) {
  const postBody = replaceAll(readPage('post-single.html'), {
    POST_TITLE: post.title,
    POST_DATE: formatDate(post.date),
    POST_BODY: sanitizePostHtml(post.html),
    BACK_URL: '/news-events/',
  });
  writeOutput(
    `news-events/${post.slug}/index.html`,
    renderPage({
      body: postBody,
      title: `${post.title} | ${SITE_TITLE}`,
      pageKey: 'news-events',
    })
  );
}

console.log('Done.');
