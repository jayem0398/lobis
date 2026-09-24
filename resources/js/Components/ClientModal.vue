<template>
  <div v-if="isOpen" class="modal-backdrop">
    <div class="modal-container">
      <div class="modal-header">
        <h3>{{ isEditing ? 'Edit Client' : 'Add New Client' }}</h3>
        <button @click="closeModal" class="btn-close">&times;</button>
      </div>

      <form @submit.prevent="saveClient" class="modal-body">
        <div class="form-group">
          <label>Client Name</label>
          <input v-model="form.client_name" type="text" required placeholder="Enter company or client name" />
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label>Contact Person</label>
            <input v-model="form.contact_person" type="text" placeholder="Full name" />
          </div>
          <div class="form-group">
            <label>Position</label>
            <input v-model="form.position" type="text" placeholder="e.g. Manager" />
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label>Contact Number</label>
            <input v-model="form.contact_number" type="text" placeholder="Phone number" />
          </div>
          <div class="form-group">
            <label>TIN Number</label>
            <input v-model="form.tin_number" type="text" placeholder="Tax identification number" />
          </div>
        </div>

        <div class="form-group">
          <label>Business Style</label>
          <input v-model="form.business_style" type="text" placeholder="Business style / Nature" />
        </div>

        <div class="form-group">
          <label>Client Address</label>
          <textarea v-model="form.client_address" rows="3" placeholder="Complete address"></textarea>
        </div>

        <div class="modal-footer">
          <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
          <button type="submit" :disabled="isSubmitting" class="btn-primary">
            {{ isSubmitting ? 'Saving...' : (isEditing ? 'Update Client' : 'Save Client') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

// Define component props
const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  clientData: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const isSubmitting = ref(false);
const isEditing = ref(false);

// Reactive form state (Credit limit removed completely)
const form = ref({
  client_name: '',
  contact_person: '',
  position: '',
  contact_number: '',
  client_address: '',
  tin_number: '',
  business_style: ''
});

// Watch for changes in clientData to switch between Add and Edit modes
watch(() => props.clientData, (newData) => {
  if (newData) {
    isEditing.value = true;
    form.value = { ...newData };
  } else {
    isEditing.value = false;
    form.value = {
      client_name: '',
      contact_person: '',
      position: '',
      contact_number: '',
      client_address: '',
      tin_number: '',
      business_style: ''
    };
  }
});

const closeModal = () => {
  emit('close');
};

const saveClient = async () => {
  isSubmitting.value = true;

  try {
    if (isEditing.value && form.value.id) {
      // Send PUT request to update client
      await axios.put(`/api/clients/${form.value.id}`, form.value);
      alert('Client updated successfully!');
    } else {
      // Send POST request to create client
      await axios.post('/api/clients', form.value);
      alert('Client created successfully!');
    }

    emit('saved');
    closeModal();
  } catch (error) {
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors;
      alert("Validation Error: " + Object.values(errors).flat().join("\n"));
    } else {
      console.error('Error saving client:', error);
      alert('Failed to save client.');
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-container {
  background: #ffffff;
  width: 100%;
  max-width: 600px;
  border-radius: 12px;
  border: 1px solid #ebedf3;
  box-shadow: 0px 10px 30px 0px rgba(76, 87, 125, 0.1);
  overflow: hidden;
}

.modal-header {
  background-color: #1e1e2d;
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  color: #ffffff;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  color: #ffffff;
  font-size: 22px;
  cursor: pointer;
}

.modal-body {
  padding: 25px;
  max-height: 75vh;
  overflow-y: auto;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: #3f4254;
  margin-bottom: 6px;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 9px 12px;
  border: 1px solid #e4e6ef;
  border-radius: 6px;
  font-size: 13px;
  color: #3f4254;
  background-color: #f3f6f9;
  outline: none;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
  border-color: #3699ff;
  background-color: #ffffff;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid #ebedf3;
  padding-top: 15px;
  margin-top: 20px;
}

.btn-primary {
  background-color: #3699ff;
  color: #ffffff;
  padding: 9px 18px;
  border: none;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  cursor: pointer;
}

.btn-secondary {
  background-color: #f3f6f9;
  color: #3f4254;
  padding: 9px 18px;
  border: 1px solid #e4e6ef;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  cursor: pointer;
}
</style>