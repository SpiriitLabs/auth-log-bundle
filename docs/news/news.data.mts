import { createContentLoader } from 'vitepress'

export interface NewsEntry {
  title: string
  url: string
  description: string
  date: { iso: string; label: string }
}

declare const data: NewsEntry[]
export { data }

export default createContentLoader('news/*.md', {
  transform: (raw): NewsEntry[] =>
    raw
      .filter(({ url }) => url !== '/news/')
      .map(({ url, frontmatter }) => ({
        title: frontmatter.title,
        url,
        description: frontmatter.description,
        date: formatDate(frontmatter.date),
      }))
      .sort((a, b) => b.date.iso.localeCompare(a.date.iso)),
})

// noon UTC, so the rendered label never drifts a day either side of the date line
function formatDate(raw: string | Date): NewsEntry['date'] {
  const date = new Date(raw)
  date.setUTCHours(12, 0, 0, 0)

  return {
    iso: date.toISOString(),
    label: date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' }),
  }
}
