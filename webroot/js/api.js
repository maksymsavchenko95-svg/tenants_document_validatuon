window.api = (function () {
    async function request(method, path, body) {
        const res = await fetch(path, {
            method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: body === undefined ? undefined : JSON.stringify(body),
        });
        const text = await res.text();
        const parsed = text ? JSON.parse(text) : null;
        if (!res.ok) {
            throw { status: res.status, body: parsed };
        }
        return parsed;
    }

    function initial() {
        const el = document.getElementById('initial');
        return el ? JSON.parse(el.textContent) : {};
    }

    return {
        initial,
        get: (p) => request('GET', p),
        post: (p, b) => request('POST', p, b),
        del: (p) => request('DELETE', p),
    };
})();
