const apiBase = '../api/index.php';

const state = {
    customers: [],
    equipment: [],
    appointments: [],
    maintenance: [],
    workOrders: [],
    reminders: [],
};

const toast = document.querySelector('#toast');
const isOpenedFromFile = window.location.protocol === 'file:';

document.querySelectorAll('.tab').forEach((tab) => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach((button) => button.classList.remove('active'));
        document.querySelectorAll('.panel').forEach((panel) => panel.classList.remove('active'));
        tab.classList.add('active');
        document.querySelector(`#${tab.dataset.tab}`).classList.add('active');
    });
});

document.querySelector('#refreshButton').addEventListener('click', loadAll);

bindForm('#customerForm', 'customers');
bindForm('#equipmentForm', 'equipment');
bindForm('#appointmentForm', 'appointments');
bindForm('#maintenanceForm', 'maintenance');
bindForm('#workOrderForm', 'work-orders');

if (isOpenedFromFile) {
    handleError(new Error('Az alkalmazást webszerverről kell megnyitni, nem fájlként. Használd például: http://localhost:8000/public/'));
} else {
    loadAll().catch(handleError);
}

function bindForm(selector, endpoint) {
    document.querySelector(selector).addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const data = Object.fromEntries(new FormData(form).entries());
        Object.keys(data).forEach((key) => {
            if (data[key] === '') {
                delete data[key];
            }
        });

        try {
            await request(endpoint, {
                method: 'POST',
                body: JSON.stringify(data),
            });

            form.reset();
            showToast('Mentés sikeres.');
            await loadAll();
        } catch (error) {
            handleError(error);
        }
    });
}

async function loadAll() {
    const [
        dashboard,
        customers,
        equipment,
        appointments,
        maintenance,
        workOrders,
        reminders,
    ] = await Promise.all([
        request('dashboard'),
        request('customers'),
        request('equipment'),
        request('appointments'),
        request('maintenance'),
        request('work-orders'),
        request('reminders'),
    ]);

    state.customers = customers;
    state.equipment = equipment;
    state.appointments = appointments;
    state.maintenance = maintenance;
    state.workOrders = workOrders;
    state.reminders = reminders;

    renderDashboard(dashboard);
    renderCustomers();
    renderEquipment();
    renderAppointments();
    renderMaintenance();
    renderWorkOrders();
    renderReminders();
    fillSelects();
}

async function request(endpoint, options = {}) {
    const { resource, id } = parseEndpoint(endpoint);
    const params = new URLSearchParams({ resource });
    if (id) {
        params.set('id', id);
    }

    let response;
    try {
        response = await fetch(`${apiBase}?${params.toString()}`, {
            headers: { 'Content-Type': 'application/json' },
            ...options,
        });
    } catch (error) {
        throw new Error('Nem érhető el a PHP API. Indíts webszervert, és http://localhost címen nyisd meg az alkalmazást.');
    }
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const detail = data.detail ? ` ${data.detail}` : '';
        throw new Error(`${data.error || 'Sikertelen kérés.'}${detail}`);
    }
    return data;
}

function parseEndpoint(endpoint) {
    const parts = endpoint.split('/');
    return {
        resource: parts[0],
        id: parts[1] || '',
    };
}

function renderDashboard(data) {
    document.querySelector('#customerCount').textContent = data.counts.customers;
    document.querySelector('#equipmentCount').textContent = data.counts.equipment;
    document.querySelector('#appointmentCount').textContent = data.counts.appointments;
    document.querySelector('#todayCount').textContent = data.today_appointments;
}

function renderCustomers() {
    document.querySelector('#customerRows').innerHTML = state.customers.map((customer) => `
        <tr>
            <td>${escapeHtml(customer.name)}</td>
            <td>${escapeHtml(customer.phone)}</td>
            <td>${escapeHtml(customer.email)}</td>
            <td>${escapeHtml(customer.address)}</td>
            <td><button class="danger" onclick="removeItem('customers', ${customer.id})">Törlés</button></td>
        </tr>
    `).join('');
}

function renderEquipment() {
    document.querySelector('#equipmentRows').innerHTML = state.equipment.map((item) => `
        <article class="card">
            <span class="badge">${escapeHtml(item.type)}</span>
            <h3>${escapeHtml(item.name)}</h3>
            <p>${escapeHtml(item.customer_name)}</p>
            <p>Gyári szám: ${escapeHtml(item.serial_number)}</p>
            <p>Következő karbantartás: ${formatDate(item.next_maintenance_date)}</p>
            <button class="danger" onclick="removeItem('equipment', ${item.id})">Törlés</button>
        </article>
    `).join('');
}

function renderAppointments() {
    document.querySelector('#appointmentRows').innerHTML = state.appointments.map((item) => `
        <tr>
            <td>${formatDateTime(item.appointment_at)}</td>
            <td>${escapeHtml(item.customer_name)}</td>
            <td>${escapeHtml(item.equipment_name || '-')}</td>
            <td>${escapeHtml(item.service_type)}</td>
            <td><span class="badge">${escapeHtml(item.status)}</span></td>
            <td><button class="danger" onclick="removeItem('appointments', ${item.id})">Törlés</button></td>
        </tr>
    `).join('');
}

function renderMaintenance() {
    document.querySelector('#maintenanceRows').innerHTML = state.maintenance.map((item) => `
        <article class="card">
            <h3>${escapeHtml(item.equipment_name)}</h3>
            <p>${escapeHtml(item.customer_name)} - ${formatDate(item.service_date)}</p>
            <p>${escapeHtml(item.description)}</p>
            <p>Szerelő: ${escapeHtml(item.technician)}</p>
            <p>Költség: ${Number(item.cost).toLocaleString('hu-HU')} Ft</p>
            <button class="danger" onclick="removeItem('maintenance', ${item.id})">Törlés</button>
        </article>
    `).join('');
}

function renderWorkOrders() {
    document.querySelector('#workOrderRows').innerHTML = state.workOrders.map((item) => `
        <article class="card">
            <span class="badge">${escapeHtml(item.work_order_number)}</span>
            <h3>${escapeHtml(item.customer_name)}</h3>
            <p>${formatDateTime(item.appointment_at)} - ${escapeHtml(item.equipment_name || 'általános munka')}</p>
            <p>${escapeHtml(item.work_summary)}</p>
            <p>Átvevő: ${escapeHtml(item.customer_signature || '-')}</p>
            <button type="button" onclick="printWorkOrder(${item.id})">Nyomtatás</button>
        </article>
    `).join('');
}

function renderReminders() {
    document.querySelector('#reminderRows').innerHTML = state.reminders.map((item) => `
        <article class="card">
            <span class="badge">${formatDate(item.next_maintenance_date)}</span>
            <h3>${escapeHtml(item.name)}</h3>
            <p>${escapeHtml(item.customer_name)} - ${escapeHtml(item.type)}</p>
            <p>${escapeHtml(item.phone)} | ${escapeHtml(item.email)}</p>
        </article>
    `).join('');
}

function fillSelects() {
    fillOptions('#equipmentCustomer', state.customers, 'Válassz ügyfelet');
    fillOptions('#appointmentCustomer', state.customers, 'Válassz ügyfelet');
    fillOptions('#appointmentEquipment', state.equipment, 'Nincs berendezés', true);
    fillOptions('#maintenanceEquipment', state.equipment, 'Válassz berendezést');
    fillOptions('#workOrderAppointment', state.appointments, 'Válassz időpontot', false, (item) => {
        return `${item.customer_name} - ${formatDateTime(item.appointment_at)}`;
    });
}

function fillOptions(selector, items, placeholder, allowEmpty = false, labelFactory = null) {
    const select = document.querySelector(selector);
    const options = items.map((item) => {
        const label = labelFactory ? labelFactory(item) : (item.name || item.customer_name);
        return `<option value="${item.id}">${escapeHtml(label)}</option>`;
    });
    select.innerHTML = `<option value="">${placeholder}</option>${options.join('')}`;
    select.required = !allowEmpty;
}

async function removeItem(endpoint, id) {
    if (!confirm('Biztosan törlöd a bejegyzést?')) {
        return;
    }
    try {
        await request(`${endpoint}/${id}`, { method: 'DELETE' });
        showToast('Törlés kész.');
        await loadAll();
    } catch (error) {
        handleError(error);
    }
}

function printWorkOrder(id) {
    const order = state.workOrders.find((item) => Number(item.id) === Number(id));
    if (!order) {
        return;
    }

    const html = `
        <h1>Munkalap: ${escapeHtml(order.work_order_number)}</h1>
        <p><strong>Ügyfél:</strong> ${escapeHtml(order.customer_name)}</p>
        <p><strong>Cím:</strong> ${escapeHtml(order.address)}</p>
        <p><strong>Időpont:</strong> ${formatDateTime(order.appointment_at)}</p>
        <p><strong>Berendezés:</strong> ${escapeHtml(order.equipment_name || '-')}</p>
        <p><strong>Munka:</strong> ${escapeHtml(order.work_summary)}</p>
        <p><strong>Anyagok:</strong> ${escapeHtml(order.materials || '-')}</p>
        <p><strong>Átvevő:</strong> ${escapeHtml(order.customer_signature || '-')}</p>
    `;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`<html><head><title>Munkalap</title></head><body>${html}</body></html>`);
    printWindow.document.close();
    printWindow.print();
}

function showToast(message) {
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2400);
}

function handleError(error) {
    console.error(error);
    showToast(error.message || 'Hiba történt a művelet közben.');
}

function formatDate(value) {
    return new Intl.DateTimeFormat('hu-HU').format(new Date(value));
}

function formatDateTime(value) {
    return new Intl.DateTimeFormat('hu-HU', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value.replace(' ', 'T')));
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[character]));
}
