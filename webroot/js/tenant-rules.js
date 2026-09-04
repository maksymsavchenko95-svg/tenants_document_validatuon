const { createApp } = Vue;

/**
 * type -> { label, fields: [{ name, kind, placeholder }] }
 * kind: 'int' | 'text' | 'list' (comma-separated)
 */
const RULE_TYPES = {
    max_size: {
        label: 'Maximum document size',
        fields: [{ name: 'max_bytes', kind: 'int', placeholder: 'bytes, e.g. 10240' }],
    },
    required_metadata: {
        label: 'Required metadata fields',
        fields: [{ name: 'fields', kind: 'list', placeholder: 'author, title' }],
    },
    prohibited_words: {
        label: 'Prohibited words',
        fields: [{ name: 'words', kind: 'list', placeholder: 'foo, bar' }],
    },
    metadata_allowed_values: {
        label: 'Metadata allowed values',
        fields: [
            { name: 'field', kind: 'text', placeholder: 'classification' },
            { name: 'values', kind: 'list', placeholder: 'public, internal, restricted' },
        ],
    },
    content_pattern: {
        label: 'Content must match pattern',
        fields: [{ name: 'pattern', kind: 'text', placeholder: 'PCRE, no delimiters' }],
    },
};

createApp({
    data() {
        const init = api.initial();
        return {
            tenant: init.tenant,
            rules: init.rules || [],
            knownTypes: (init.types || []).filter((t) => RULE_TYPES[t]),
            RULE_TYPES,
            draftType: '',
            draftInput: {},
            formError: null,
            busy: false,
        };
    },
    computed: {
        draftFields() {
            return this.draftType ? RULE_TYPES[this.draftType].fields : [];
        },
    },
    methods: {
        onTypeChange() {
            this.draftInput = {};
            this.formError = null;
        },
        buildConfig() {
            const cfg = {};
            for (const f of this.draftFields) {
                const raw = this.draftInput[f.name];
                if (raw === undefined || raw === '') continue;
                if (f.kind === 'int') cfg[f.name] = Number.parseInt(raw, 10);
                else if (f.kind === 'list') cfg[f.name] = String(raw).split(',').map((s) => s.trim()).filter(Boolean);
                else cfg[f.name] = raw;
            }
            return cfg;
        },
        async add() {
            this.formError = null;
            this.busy = true;
            try {
                const rule = await api.post(`/tenants/${this.tenant.id}/rules`, {
                    type: this.draftType,
                    config: this.buildConfig(),
                });
                this.rules.push(rule);
                this.draftType = '';
                this.draftInput = {};
            } catch (e) {
                this.formError = (e.body && e.body.errors && (e.body.errors.config || e.body.errors.type))
                    || (e.body && e.body.message) || `Request failed (${e.status || '?'})`;
                if (typeof this.formError !== 'string') this.formError = JSON.stringify(this.formError);
            } finally {
                this.busy = false;
            }
        },
        async remove(rule) {
            try {
                await api.del(`/tenants/${this.tenant.id}/rules/${rule.id}`);
                this.rules = this.rules.filter((r) => r.id !== rule.id);
            } catch (e) {
                this.formError = (e.body && e.body.message) || `Delete failed (${e.status || '?'})`;
            }
        },
    },
    template: `
        <ul class="summaries" v-if="rules.length">
            <li v-for="r in rules" :key="r.id">
                <span>{{ r.summary }}</span>
                <button type="button" class="ghost danger" @click="remove(r)">Delete</button>
            </li>
        </ul>
        <p v-else class="muted">No rules — every document for this tenant passes.</p>

        <form class="add-rule" @submit.prevent="add">
            <select v-model="draftType" @change="onTypeChange">
                <option value="">Add a rule…</option>
                <option v-for="t in knownTypes" :key="t" :value="t">{{ RULE_TYPES[t].label }}</option>
            </select>
            <label v-for="f in draftFields" :key="f.name">
                {{ f.name.replace(/_/g, ' ') }}
                <input v-model="draftInput[f.name]" :type="f.kind === 'int' ? 'number' : 'text'" :placeholder="f.placeholder">
            </label>
            <button type="submit" v-if="draftType" :disabled="busy">Add rule</button>
        </form>
        <p v-if="formError" class="inline-error">{{ formError }}</p>
    `,
}).mount('#app');
