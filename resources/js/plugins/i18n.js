import { createI18n } from 'vue-i18n';
import en from '../locales/en.json';
import hi from '../locales/hi.json';
import mr from '../locales/mr.json';
import kn from '../locales/kn.json';

const LOCALE_KEY = 'school_erp_locale';

export const supportedLocales = [
    { code: 'en', name: 'English',  flag: '🇬🇧' },
    { code: 'hi', name: 'हिंदी',   flag: '🇮🇳' },
    { code: 'mr', name: 'मराठी',   flag: '🇮🇳' },
    { code: 'kn', name: 'ಕನ್ನಡ',  flag: '🇮🇳' },
];

function getInitialLocale() {
    try {
        const saved = localStorage.getItem(LOCALE_KEY);
        if (saved && supportedLocales.some(l => l.code === saved)) return saved;
    } catch (_) {}
    // Detect browser language
    const browserLang = navigator.language?.split('-')[0];
    if (['hi', 'mr', 'kn'].includes(browserLang)) return browserLang;
    return 'en';
}

export const i18n = createI18n({
    legacy: false,
    locale: getInitialLocale(),
    fallbackLocale: 'en',
    messages: { en, hi, mr, kn },
    silentTranslationWarn: true,
    missingWarn: false,
});

export function setLocale(code) {
    i18n.global.locale.value = code;
    try { localStorage.setItem(LOCALE_KEY, code); } catch (_) {}
    document.documentElement.lang = code;
    // For RTL langs, adjust direction (Hindi/Marathi are LTR)
    document.documentElement.dir = 'ltr';
}
