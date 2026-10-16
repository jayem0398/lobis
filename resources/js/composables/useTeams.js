import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

export function useTeams() {
  const toast = useToast();

  // ── State ──────────────────────────────────────────────────────────────────
  const teams = ref([]);
  const searchQuery = ref('');
  const showModal = ref(false);
  const showEditModal = ref(false);
  const showDeleteModal = ref(false);
  const selectedTeamForDeletion = ref(null);
  const showEditConfirmModal = ref(false);
  const isSubmitting = ref(false);
  const isLoading = ref(true);

  const form = ref({
    id: null,
    team_name: '',
    quota: 0
  });

  const editForm = ref({
    id: null,
    team_name: '',
    quota: 0
  });

  // ── Computeds ─────────────────────────────────────────────────────────────
  const totalMembersCount = computed(() => {
    return teams.value.reduce((acc, t) => acc + (Number(t.users_count) || 0), 0);
  });

  const totalQuotaSum = computed(() => {
    return teams.value.reduce((acc, t) => acc + (Number(t.quota) || 0), 0);
  });

  const filteredTeams = computed(() => {
    return teams.value.filter(t => {
      const name = t.team_name || '';
      return name.toLowerCase().includes(searchQuery.value.toLowerCase());
    });
  });

  // ── Helpers ───────────────────────────────────────────────────────────────
  const formatCurrency = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
  const formatDate = (dateStr) => (dateStr ? new Date(dateStr).toISOString().split('T')[0] : '');

  // ── Actions ───────────────────────────────────────────────────────────────
  const fetchTeams = async () => {
    isLoading.value = true;
    try {
      const res = await axios.get('/api/teams');
      teams.value = res.data.data || res.data;
    } catch (error) {
      console.error('Error fetching teams:', error);
      toast.error('Failed to load teams data.');
    } finally {
      isLoading.value = false;
    }
  };

  const openAddModal = () => {
    form.value = {
      id: null,
      team_name: '',
      quota: 0
    };
    showModal.value = true;
  };

  const openEditModal = (team) => {
    editForm.value = {
      id: team.id,
      team_name: team.team_name,
      quota: team.quota
    };
    showEditModal.value = true;
  };

  const promptDeleteTeam = (team) => {
    selectedTeamForDeletion.value = team;
    showDeleteModal.value = true;
  };

  const executeDeleteTeam = async () => {
    if (!selectedTeamForDeletion.value) return;
    isSubmitting.value = true;
    try {
      const id = selectedTeamForDeletion.value.id;
      await axios.delete(`/api/teams/${id}`);
      teams.value = teams.value.filter(t => t.id !== id);
      toast.success('Team deleted successfully.');
      showDeleteModal.value = false;
    } catch (error) {
      console.error('Error deleting team:', error);
      toast.error('Failed to delete team.');
    } finally {
      isSubmitting.value = false;
    }
  };

  const saveTeam = async () => {
    isSubmitting.value = true;
    try {
      await axios.post('/api/teams', form.value);
      toast.success('Team created successfully!');
      showModal.value = false;
      await fetchTeams();
    } catch (error) {
      console.error('Error saving team:', error.response?.data || error.message);
      toast.error(error.response?.data?.message || 'Failed to save team.');
    } finally {
      isSubmitting.value = false;
    }
  };

  const promptUpdateTeam = () => {
    showEditConfirmModal.value = true;
  };

  const executeUpdateTeam = async () => {
    isSubmitting.value = true;
    try {
      await axios.put(`/api/teams/${editForm.value.id}`, editForm.value);
      toast.success('Team updated successfully!');
      showEditConfirmModal.value = false;
      showEditModal.value = false;
      await fetchTeams();
    } catch (error) {
      console.error('Error updating team:', error.response?.data || error.message);
      toast.error(error.response?.data?.message || 'Failed to update team.');
    } finally {
      isSubmitting.value = false;
    }
  };

  const initTeams = async () => {
    await fetchTeams();
  };

  onMounted(() => {
    initTeams();
  });

  return {
    // State
    teams, searchQuery, showModal, showEditModal,
    showDeleteModal, selectedTeamForDeletion, showEditConfirmModal,
    isSubmitting, isLoading, form, editForm,

    // Computeds
    totalMembersCount, totalQuotaSum, filteredTeams,

    // Helpers & Actions
    formatCurrency, formatDate, fetchTeams, openAddModal,
    openEditModal, promptDeleteTeam, executeDeleteTeam, saveTeam,
    promptUpdateTeam, executeUpdateTeam, initTeams
  };
}
