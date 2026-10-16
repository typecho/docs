import React from 'react';
import Content from '@theme-original/DocItem/Content';
import Link from '@docusaurus/Link';
import Translate from '@docusaurus/Translate';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import {useDoc} from '@docusaurus/plugin-content-docs/client';

// Shows a notice above the doc body when the current locale falls back to the
// Chinese source file, or when the translation is marked `translation: machine`.
// See OUTLINE.md §5.4.
function TranslationBanner() {
  const {
    i18n: {currentLocale, defaultLocale},
  } = useDocusaurusContext();
  const {metadata, frontMatter} = useDoc();

  // metadata.source is "@site/<path relative to site dir>"; a localized file
  // lives under i18n/<locale>/, otherwise the default-locale source is used.
  const isFallback =
    currentLocale !== defaultLocale &&
    !metadata.source.startsWith(`@site/i18n/${currentLocale}/`);

  if (isFallback) {
    return (
      <div
        className="alert alert--warning margin-bottom--md"
        data-i18n-banner="untranslated">
        <Translate
          id="theme.docs.translationBanner.untranslated"
          description="Shown on a doc page that has no translation for the current locale">
          本页尚未翻译，正在显示中文原文
        </Translate>{' '}
        <Link to="/develop/contributing/docs">
          <Translate
            id="theme.docs.translationBanner.helpTranslate"
            description="Link in the untranslated banner to the contributing guide">
            帮助翻译
          </Translate>
        </Link>
      </div>
    );
  }

  if (frontMatter.translation === 'machine') {
    return (
      <div
        className="alert alert--info margin-bottom--md"
        data-i18n-banner="machine">
        <Translate
          id="theme.docs.translationBanner.machine"
          description="Shown on a doc page whose translation is machine-generated and not yet reviewed">
          本页为机器翻译，尚未校对
        </Translate>
      </div>
    );
  }

  return null;
}

export default function ContentWrapper(props) {
  return (
    <>
      <TranslationBanner />
      <Content {...props} />
    </>
  );
}
