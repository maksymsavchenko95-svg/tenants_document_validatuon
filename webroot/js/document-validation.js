const {createApp} = Vue;

createApp({
    data() {
        const init = api.initial();
        return {
            tenant: init.tenant,
            ruleSummaries: init.rules || [],
            documentId: '',
            content: '',
            metadata: [{key: '', value: ''}],
            result: null,
            requestError: null,
            busy: false,
        };
    },
    computed: {
        byteCount() {
            return new TextEncoder().encode(this.content).length;
        },
    },
    methods: {
        addRow() {
            this.metadata.push({key: '', value: ''});
        },
        removeRow(i) {
            this.metadata.splice(i, 1);
        },
        loadFromFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                this.content = String(reader.result);
                this.upsertMeta('filename', file.name);
                this.upsertMeta('size', String(file.size));
            };
            reader.readAsText(file);
        },
        upsertMeta(key, value) {
            const row = this.metadata.find((m) => m.key === key);
            if (row) row.value = value;
            else this.metadata.push({key, value});
        },
        async submit() {
            this.busy = true;
            this.requestError = null;
            this.result = null;
            const metadata = {};
            for (const row of this.metadata) {
                if (row.key) metadata[row.key] = row.value;
            }
            try {
                this.result = await api.post(`/tenants/${this.tenant.id}/validate`, {
                    documentId: this.documentId,
                    content: this.content,
                    metadata
                });
            } catch (e) {
                this.requestError = (e.body && e.body.message) || `Request failed (${e.status || '?'})`;
            } finally {
                this.busy = false;
                this.$nextTick(() => this.$refs.result?.scrollIntoView({behavior: 'smooth', block: 'nearest'}));
            }
        },
    },
    template: `
        <section class="card">
            <h2>Rules applied</h2>
            <ul class="summaries" v-if="ruleSummaries.length">
                <li v-for="(s, i) in ruleSummaries" :key="i">{{ s }}</li>
            </ul>
            <p v-else class="muted">No rules — every document passes.</p>
        </section>

        <div class="result" ref="result" v-if="result" :class="result.isValid ? 'ok' : 'bad'">
            <strong v-if="result.isValid">✓ Valid</strong>
            <strong v-else>✗ Invalid</strong>
            <ul v-if="result.errors.length"><li v-for="(e, i) in result.errors" :key="i">{{ e }}</li></ul>
        </div>
        <p v-if="requestError" class="inline-error">{{ requestError }}</p>

        <form class="card" @submit.prevent="submit">
            <label>
                Document ID
                <input type="text" placeholder="DOC-123" v-model="documentId" required>
            </label>
            <label>
                Content
                <textarea v-model="content" rows="6" placeholder="Document body text…"></textarea>
            </label>
            <p class="muted">{{ byteCount }} bytes
                <label class="file-btn">· Load from file…
                    <input type="file" accept=".txt,.md,.json" @change="loadFromFile" hidden>
                </label>
            </p>

            <fieldset>
                <legend>Metadata</legend>
                <div class="meta-row" v-for="(m, i) in metadata" :key="i">
                    <input v-model.trim="m.key" placeholder="field">
                    <input v-model.trim="m.value" placeholder="value">
                    <button type="button" class="ghost" @click="removeRow(i)">&times;</button>
                </div>
                <button type="button" class="ghost" @click="addRow">+ row</button>
            </fieldset>

            <button type="submit" :disabled="busy">{{ busy ? 'Validating…' : 'Validate' }}</button>
        </form>
    `,
}).mount('#app');
