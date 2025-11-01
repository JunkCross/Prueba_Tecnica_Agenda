<template>
  <div class="calendar-container">
    <FullCalendar
      ref="calendarRef"
      :options="calendarOptions"
    />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import timelinePlugin from '@fullcalendar/timeline'
import resourceTimelinePlugin from '@fullcalendar/resource-timeline'
import interactionPlugin from '@fullcalendar/interaction'
import axios from 'axios'

import '../calendar.css'



export default {
  components: { FullCalendar },
  setup() {
    const calendarRef = ref(null)

    const api = axios.create({
      baseURL: 'http://localhost:8000/api',
      headers: { 'Content-Type': 'application/json' }
    })

    const loadSchedules = async () => {
      const { data } = await api.get('/schedules')
      const todayStr = new Date().toISOString().slice(0,10)
      return data.map(s => ({
        id: s.id,
        title: `${s.start_time} - ${s.end_time}`,
        start: `${todayStr}T${s.start_time}:00`,
        end:   `${todayStr}T${s.end_time}:00`,
        resourceId: String(s.day_of_week)
      }))
    }

    const refetchFromApi = async () => {
      const cal = calendarRef.value.getApi()
      cal.removeAllEvents()
      ;(await loadSchedules()).forEach(e => cal.addEvent(e))
    }

    // --- Handler de guardado (llamado desde options.select) ---
    const handleDateSelect = async (info) => {
      const resource = info.resource
      if (!resource) { alert('Seleccione sobre un renglón (día).'); return }

      const startTime = info.start.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: false })
      const endTime   = info.end  .toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: false })

      if (!confirm(`¿Desea guardar este horario?\nHora inicio: ${startTime}\nHora fin: ${endTime}`)) return

      const payload = {
        day_of_week: parseInt(resource.id, 10), 
        start_time: startTime,                 
        end_time: endTime
      }

      try {
        const { data } = await api.post('/schedules', payload)
        console.log('Guardado OK:', data)
        await refetchFromApi()
      } catch (e) {
        console.error('Error al guardar el horario:', e)
        alert('No se pudo guardar el horario')
      }
    }

    const handleEventClick = async (clickInfo) => {
      const id = clickInfo.event.id
      if (!id) { alert('Evento sin ID'); return }

      if (!confirm('¿Desea eliminar este horario?')) return

      try {
        const { data, status } = await api.delete(`/schedules/${id}`)
        if (status === 200 && data?.status === 'ok') {
          clickInfo.event.remove()  
        } else {
          await refetchFromApi()
        }
      } catch (e) {
        console.error('Error al eliminar:', e)
        alert('No se pudo eliminar el horario')
      }
    }

    const calendarOptions = ref({
      plugins: [timelinePlugin, resourceTimelinePlugin, interactionPlugin],
      initialView: 'resourceTimelineDay',
      slotMinTime: '01:00:00',
      slotMaxTime: '23:00:00',
      selectable: true,
      selectMirror: true,
      headerToolbar: false,
      slotDuration: '00:30:00',
      height: 'auto',
      contentHeight: 'auto',
      resourceAreaWidth: '90px',
      resourceAreaHeaderContent: 'Días',
      snapDuration: '00:30:00',
      slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
      resources: [
        { id: '1', title: '1 Lun' },
        { id: '2', title: '2 Mar' },
        { id: '3', title: '3 Mié' },
        { id: '4', title: '4 Jue' },
        { id: '5', title: '5 Vie' },
        { id: '6', title: '6 Sáb' },
        { id: '7', title: '7 Dom' }
      ],
      events: [],
      eventClick: (clickInfo) => {
        handleEventClick(clickInfo)
      },

      selectAllow: (info) => {
        // formatea horas/minutos
        const pad = (n) => String(n).padStart(2, '0');
        const toHM = (d) => `${pad(d.getHours())}:${pad(d.getMinutes())}`;

        const startHM = toHM(info.start);
        const endHM   = toHM(info.end);

        const diffMs = info.end - info.start;
        const h = Math.floor(diffMs / 3600000);
        const m = Math.round((diffMs % 3600000) / 60000);

        const label = `${startHM} – ${endHM} (${h}h${m ? m + 'm' : ''})`;

        // espera al siguiente frame para asegurar que el highlight exista en el DOM
        requestAnimationFrame(() => {
          document.querySelectorAll('.fc-highlight')
            .forEach(el => el.setAttribute('data-time', label));
        });

        return true;
      },


      
      select: (info) => {
        const diff = (info.end - info.start) / (1000 * 60 * 60)
        const h = Math.floor(diff)
        const m = Math.round((diff - h) * 60)
        document.querySelectorAll('.fc-highlight')
          .forEach(el => el.setAttribute('data-time', `${h}h${m ? m + 'm' : ''}`))

        handleDateSelect(info)
      }
    })

    onMounted(async () => {
      await refetchFromApi()
    })

    return { calendarOptions, handleEventClick, calendarRef }
  }
}
</script>




