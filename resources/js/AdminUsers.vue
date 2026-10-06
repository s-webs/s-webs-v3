<script setup>
import { onMounted, reactive, ref } from 'vue';
import { PhFloppyDisk, PhPencilSimple, PhPlus, PhTrash, PhX } from '@phosphor-icons/vue';

const admins = ref([]);
const currentId = ref(null);
const form = reactive({ name: '', email: '', password: '' });
const busy = ref(false);
const error = ref('');
const notice = ref('');
const csrf = document.querySelector('meta[name="csrf-token"]').content;
async function api(method, url, body) {
  const response = await fetch(url, { method, credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: body ? JSON.stringify(body) : undefined });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || `Ошибка ${response.status}`);
  return data;
}
async function load() {
  try { admins.value = (await api('GET', '/admin/admins')).admins; }
  catch (e) { error.value = e.message; }
}
function edit(admin) { currentId.value = admin.id; Object.assign(form, { name: admin.name, email: admin.email, password: '' }); error.value = ''; notice.value = ''; }
function reset() { currentId.value = null; Object.assign(form, { name: '', email: '', password: '' }); }
async function save() {
  busy.value = true; error.value = ''; notice.value = '';
  try { const data = await api(currentId.value ? 'PUT' : 'POST', currentId.value ? `/admin/admins/${currentId.value}` : '/admin/admins', form); notice.value = data.message; reset(); await load(); }
  catch (e) { error.value = e.message; }
  finally { busy.value = false; }
}
async function remove(admin) {
  if (!window.confirm(`Удалить администратора «${admin.name}»?`)) return;
  busy.value = true; error.value = ''; notice.value = '';
  try { const data = await api('DELETE', `/admin/admins/${admin.id}`); notice.value = data.message; if (currentId.value === admin.id) reset(); await load(); }
  catch (e) { error.value = e.message; }
  finally { busy.value = false; }
}
onMounted(load);
</script>

<template>
  <div class="admin-users">
    <div class="panel form-panel"><div class="section-heading"><h2>{{ currentId ? 'Изменить администратора' : 'Новый администратор' }}</h2><p>Доступ к управлению контентом и настройками сайта.</p></div>
      <p v-if="error" class="notice error" role="alert">{{ error }}</p><p v-if="notice" class="notice success" role="status">{{ notice }}</p>
      <div class="settings-grid"><div class="field"><label for="admin-name">Имя</label><input id="admin-name" v-model.trim="form.name" type="text" autocomplete="name"></div><div class="field"><label for="admin-email">Email</label><input id="admin-email" v-model.trim="form.email" type="email" autocomplete="email"></div><div class="field"><label for="admin-password">{{ currentId ? 'Новый пароль' : 'Пароль' }}</label><input id="admin-password" v-model="form.password" type="password" autocomplete="new-password" minlength="12"><small>{{ currentId ? 'Оставьте пустым, чтобы не менять пароль.' : 'Не менее 12 символов.' }}</small></div></div>
      <div class="settings-actions"><button class="button primary" type="button" :disabled="busy" @click="save"><PhFloppyDisk v-if="currentId" :size="17" /><PhPlus v-else :size="17" /> {{ currentId ? 'Сохранить' : 'Добавить' }}</button><button v-if="currentId" class="button" type="button" @click="reset"><PhX :size="17" /> Отмена</button></div>
    </div>
    <div class="panel table-wrap"><table><thead><tr><th>Имя</th><th>Email</th><th>Создан</th><th class="actions-col">Действия</th></tr></thead><tbody><tr v-for="admin in admins" :key="admin.id"><td><strong>{{ admin.name }}</strong></td><td>{{ admin.email }}</td><td>{{ new Date(admin.created_at).toLocaleDateString('ru-RU') }}</td><td><div class="row-actions"><button :aria-label="`Изменить ${admin.name}`" @click="edit(admin)"><PhPencilSimple :size="18" /></button><button class="delete" :aria-label="`Удалить ${admin.name}`" @click="remove(admin)"><PhTrash :size="18" /></button></div></td></tr></tbody></table></div>
  </div>
</template>
