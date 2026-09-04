const { createApp } = Vue;

createApp({
    data() {
        return {
            tenants: api.initial().tenants || [],
            newName: '',
            formError: null,
            busy: false,
        };
    },
    methods: {
        async add() {
            this.formError = null;
            this.busy = true;
            try {
                const created = await api.post('/tenants', { name: this.newName });
                this.tenants.push({ id: created.id, name: created.name, ruleCount: 0, created: created.created });
                this.tenants.sort((a, b) => a.name.localeCompare(b.name));
                this.newName = '';
            } catch (e) {
                this.formError = (e.body && e.body.errors && e.body.errors.name && e.body.errors.name[0])
                    || (e.body && e.body.message) || `Request failed (${e.status || '?'})`;
            } finally {
                this.busy = false;
            }
        },
        async remove(tenant) {
            if (!confirm(`Delete "${tenant.name}" and all its rules?`)) return;
            try {
                await api.del(`/tenants/${tenant.id}`);
                this.tenants = this.tenants.filter((t) => t.id !== tenant.id);
            } catch (e) {
                this.formError = (e.body && e.body.message) || `Delete failed (${e.status || '?'})`;
            }
        },
        fmtDate(iso) {
            return iso ? new Date(iso).toLocaleDateString() : '';
        },
    },
    template: `
        <table class="rules" v-if="tenants.length">
            <thead><tr><th>Name</th><th># rules</th><th>Created</th><th></th></tr></thead>
            <tbody>
                <tr v-for="t in tenants" :key="t.id">
                    <td>{{ t.name }}</td>
                    <td>{{ t.ruleCount }}</td>
                    <td>{{ fmtDate(t.created) }}</td>
                    <td class="actions">
                        <a :href="'/tenants/' + t.id + '/rules'">Rules</a>
                        <a :href="'/tenants/' + t.id + '/validate'">Validate</a>
                        <button type="button" class="ghost danger" @click="remove(t)">Delete</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <p v-else class="muted">No tenants yet.</p>

        <form class="add-rule" @submit.prevent="add">
            <input v-model.trim="newName" placeholder="New tenant name" required>
            <button type="submit" :disabled="busy || !newName">Add tenant</button>
        </form>
        <p v-if="formError" class="inline-error">{{ formError }}</p>
    `,
}).mount('#app');
