<!--
  Matomo - free/libre analytics platform

  Openmost Audit plugin

  @link    https://openmost.com
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div
    :class="[
      'audit-finding',
      `status-${finding.status}`,
      open ? 'is-open' : 'is-collapsed',
    ]"
  >
    <div
      :class="['finding-header', open ? 'is-open' : 'is-collapsed']"
      role="button"
      tabindex="0"
      :aria-expanded="open ? 'true' : 'false'"
      @click="toggle"
      @keydown="onKeydown"
    >
      <span :class="['sev-pill', `severity-${finding.severity || 'info'}`]">
        {{ severityLabel }}
      </span>
      <span class="finding-title">{{ finding.title }}</span>
      <span v-if="isPremium" class="audit-premium-badge">{{ translate('Audit_StatusPremium') }}</span>
      <span class="finding-id">{{ finding.itemId }}</span>
      <span class="finding-toggle" aria-hidden="true">{{ open ? '−' : '+' }}</span>
    </div>

    <div v-if="open && isPremium" class="finding-content">
      <p class="finding-premium">
        {{ translate('Audit_PremiumCheckText') }}
        <a :href="premiumUrl" target="_blank" rel="noopener" @click.stop>
          {{ translate('Audit_PremiumLinkText') }}
        </a>
      </p>
    </div>

    <div v-if="open && !isPremium && hasDetails" class="finding-content">
      <!--
        The detail / recommendation fields are server-rendered from inline
        Markdown (see PHP `Support\InlineMarkdown`) so we use v-html together
        with Matomo's global $sanitize filter instead of a mustache
        interpolation. The raw Markdown is never sent to the browser.
      -->
      <div
        v-if="finding.detailHtml || finding.detail"
        class="finding-body"
        v-html="$sanitize(finding.detailHtml || finding.detail)"
      />

      <div class="finding-kv" v-if="finding.currentValue || finding.expectedValue">
        <div v-if="finding.currentValue">
          <span class="kv-label">{{ translate('Audit_CurrentValue') }}:</span>
          <span class="kv-value">{{ finding.currentValue }}</span>
        </div>
        <div v-if="finding.expectedValue">
          <span class="kv-label">{{ translate('Audit_ExpectedValue') }}:</span>
          <span class="kv-value">{{ finding.expectedValue }}</span>
        </div>
      </div>

      <div
        class="finding-reco"
        v-if="finding.recommendationHtml || finding.recommendation || finding.codeSnippet"
      >
        <div
          v-if="finding.recommendationHtml || finding.recommendation"
          class="finding-reco-body"
          v-html="$sanitize(finding.recommendationHtml || finding.recommendation)"
        />
        <pre v-if="finding.codeSnippet">{{ finding.codeSnippet }}</pre>
      </div>

      <div class="finding-refs" v-if="finding.references && finding.references.length">
        <span class="kv-label">{{ translate('Audit_References') }}:</span>
        <a
          v-for="(ref, idx) in finding.references"
          :key="idx"
          :href="ref"
          target="_blank"
          rel="noopener"
          @click.stop
        >{{ ref }}</a>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';
import { translate } from 'CoreHome';

export interface FindingDto {
  itemId: string;
  title: string;
  category: string;
  status: string;
  severity: string;
  detail: string;
  detailHtml?: string;
  currentValue: string | null;
  expectedValue: string | null;
  recommendation: string | null;
  recommendationHtml?: string;
  codeSnippet: string | null;
  references: string[];
}

const severityLabelKey: Record<string, string> = {
  critical: 'Audit_SeverityCritical',
  high: 'Audit_SeverityHigh',
  medium: 'Audit_SeverityMedium',
  low: 'Audit_SeverityLow',
  info: 'Audit_SeverityInfo',
};

export default defineComponent({
  props: {
    finding: {
      type: Object as PropType<FindingDto>,
      required: true,
    },
    premiumUrl: {
      type: String,
      default: '',
    },
  },
  data() {
    return { open: false };
  },
  computed: {
    isPremium(): boolean {
      return this.finding.status === 'premium';
    },
    hasDetails(): boolean {
      const f = this.finding;
      return !!(
        f.detail
        || f.detailHtml
        || f.currentValue
        || f.expectedValue
        || f.recommendation
        || f.recommendationHtml
        || f.codeSnippet
        || (f.references && f.references.length)
      );
    },
    severityLabel(): string {
      const key = severityLabelKey[this.finding.severity];
      return key ? translate(key) : this.finding.severity;
    },
  },
  methods: {
    translate,
    toggle() { this.open = !this.open; },
    onKeydown(event: KeyboardEvent) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        this.toggle();
      }
    },
  },
});
</script>
