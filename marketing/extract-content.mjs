import { readFileSync, writeFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const sqlPath =
  'C:/Users/rahul/Downloads/positivetree-ngo/dup-installer/dup-database__d2ac2b7-19104644.sql';

const sql = readFileSync(sqlPath, 'utf8');

function parseInsertRows(tableName) {
  const pattern = new RegExp(
    `INSERT INTO \`${tableName}\` VALUES ([\\s\\S]*?);\\n`,
    'g'
  );
  const rows = [];
  let match;
  while ((match = pattern.exec(sql)) !== null) {
    rows.push(match[1]);
  }
  return rows.join(',');
}

function splitSqlTuples(blob) {
  const tuples = [];
  let depth = 0;
  let current = '';
  let inString = false;
  let escape = false;

  for (let i = 0; i < blob.length; i++) {
    const ch = blob[i];
    if (escape) {
      current += ch;
      escape = false;
      continue;
    }
    if (ch === '\\' && inString) {
      current += ch;
      escape = true;
      continue;
    }
    if (ch === "'" && !inString) {
      inString = true;
      current += ch;
      continue;
    }
    if (ch === "'" && inString) {
      if (blob[i + 1] === "'") {
        current += "''";
        i++;
        continue;
      }
      inString = false;
      current += ch;
      continue;
    }
    if (!inString && ch === '(') {
      if (depth === 0) current = '';
      depth++;
      if (depth > 1) current += ch;
      continue;
    }
    if (!inString && ch === ')') {
      depth--;
      if (depth === 0) {
        tuples.push(current);
        current = '';
        continue;
      }
      if (depth > 0) current += ch;
      continue;
    }
    if (depth > 0) current += ch;
  }
  return tuples;
}

function unquoteField(field) {
  const trimmed = field.trim();
  if (trimmed === 'NULL') return null;
  if (trimmed.startsWith("'")) {
    return trimmed
      .slice(1, -1)
      .replace(/\\'/g, "'")
      .replace(/\\n/g, '\n')
      .replace(/\\r/g, '\r')
      .replace(/\\\\/g, '\\');
  }
  return trimmed;
}

function parseTuple(tuple) {
  const fields = [];
  let current = '';
  let inString = false;
  let escape = false;

  for (let i = 0; i < tuple.length; i++) {
    const ch = tuple[i];
    if (escape) {
      current += ch;
      escape = false;
      continue;
    }
    if (ch === '\\' && inString) {
      current += ch;
      escape = true;
      continue;
    }
    if (ch === "'" && !inString) {
      inString = true;
      current += ch;
      continue;
    }
    if (ch === "'" && inString) {
      if (tuple[i + 1] === "'") {
        current += "''";
        i++;
        continue;
      }
      inString = false;
      current += ch;
      continue;
    }
    if (!inString && ch === ',') {
      fields.push(unquoteField(current));
      current = '';
      continue;
    }
    current += ch;
  }
  if (current.length) fields.push(unquoteField(current));
  return fields;
}

const postsBlob = parseInsertRows('tdm_posts');
const postTuples = splitSqlTuples(postsBlob);

const posts = postTuples
  .map((t) => {
    const f = parseTuple(t);
    return {
      id: Number(f[0]),
      author: f[1],
      date: f[2],
      content: f[4] || '',
      title: f[5] || '',
      excerpt: f[6] || '',
      status: f[7],
      slug: f[11] || '',
      type: f[20] || '',
      mime: f[21] || '',
    };
  })
  .filter((p) => p.status === 'publish');

const pages = posts.filter((p) => p.type === 'page');
const blogPosts = posts.filter((p) => p.type === 'post');

const termsBlob = parseInsertRows('tdm_terms');
const termTuples = splitSqlTuples(termsBlob);
const terms = termTuples.map((t) => {
  const f = parseTuple(t);
  return { id: Number(f[0]), name: f[1], slug: f[2] };
});

const taxBlob = parseInsertRows('tdm_term_taxonomy');
const taxTuples = splitSqlTuples(taxBlob);
const taxonomies = taxTuples.map((t) => {
  const f = parseTuple(t);
  return { id: Number(f[0]), termId: Number(f[1]), taxonomy: f[2], description: f[3] || '' };
});

const relBlob = parseInsertRows('tdm_term_relationships');
const relTuples = splitSqlTuples(relBlob);
const relationships = relTuples.map((t) => {
  const f = parseTuple(t);
  return { objectId: Number(f[0]), termTaxonomyId: Number(f[1]) };
});

function stripHtml(html) {
  return html
    .replace(/<!--[\s\S]*?-->/g, '')
    .replace(/<style[\s\S]*?<\/style>/gi, '')
    .replace(/<script[\s\S]*?<\/script>/gi, '')
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function simplifyContent(html) {
  if (!html) return '';
  let out = html
    .replace(/<!-- wp:[\s\S]*?-->/g, '')
    .replace(/<!-- \/wp:[\s\S]*?-->/g, '')
    .replace(/<style[\s\S]*?<\/style>/gi, '')
    .replace(/<script[\s\S]*?<\/script>/gi, '');
  return out.trim();
}

const categoryMap = {};
for (const tax of taxonomies) {
  if (tax.taxonomy === 'category') {
    const term = terms.find((t) => t.id === tax.termId);
    if (term) categoryMap[tax.id] = term;
  }
}

const postsWithCategories = blogPosts.map((post) => {
  const rels = relationships.filter((r) => r.objectId === post.id);
  const categories = rels
    .map((r) => categoryMap[r.termTaxonomyId])
    .filter(Boolean);
  return {
    ...post,
    categories,
    plainText: stripHtml(post.content),
    html: simplifyContent(post.content),
  };
});

const output = {
  pages: pages.map((p) => ({
    id: p.id,
    title: p.title,
    slug: p.slug,
    html: simplifyContent(p.content),
    plainText: stripHtml(p.content),
  })),
  posts: postsWithCategories.map((p) => ({
    id: p.id,
    title: p.title,
    slug: p.slug,
    date: p.date,
    excerpt: stripHtml(p.excerpt),
    html: p.html,
    categories: p.categories.map((c) => ({ name: c.name, slug: c.slug })),
  })),
  categories: Object.values(categoryMap).map((t) => ({
    name: t.name,
    slug: t.slug,
  })),
};

writeFileSync(
  join(__dirname, 'data/content.json'),
  JSON.stringify(output, null, 2),
  'utf8'
);

console.log(
  `Extracted ${output.pages.length} pages, ${output.posts.length} posts, ${output.categories.length} categories`
);
output.pages.forEach((p) => console.log(`  page: ${p.slug} — ${p.title}`));
