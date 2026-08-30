import { h } from 'vue'
import Theme from 'vitepress/theme'
import { withBase } from 'vitepress'
import './styles.css'

export default {
  extends: Theme,
  Layout() {
    return h(Theme.Layout, null, {
      'home-hero-info-before': () =>
        h('a', { class: 'owasp-badge', href: withBase('/owasp') }, [
          h('span', { class: 'owasp-badge-tag' }, 'OWASP A09'),
          h('span', null, 'Security Logging & Monitoring'),
        ]),
      'home-features-before': () =>
        h('section', { class: 'owasp-band' }, [
          h('div', { class: 'owasp-band-inner' }, [
            h('p', { class: 'owasp-band-kicker' }, 'The #1 symptom of OWASP A09:2021'),
            h('blockquote', { class: 'owasp-band-quote' },
              '“Auditable events, such as logins, failed logins, and high-value transactions, are not logged.”'),
            h('p', { class: 'owasp-band-text' }, [
              'An attacker with a stolen password doesn’t fail to log in — they succeed, silently. ',
              'Recording every login context is the only way to see it, and this bundle closes that gap.',
            ]),
            h('a', { class: 'owasp-band-cta', href: withBase('/owasp') }, 'Read the security rationale →'),
          ]),
        ]),
    })
  },
}
