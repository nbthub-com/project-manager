import axios from 'axios'
import { usePage } from '@inertiajs/vue3'

export async function useNotes(note, context = 'task', context_ = null, notes = []) {
  const user = usePage().props.auth.user
  const content = note.trim();
  if (!content) return; // prevent empty notes

  try {
    const response = await axios.post("/notes", {
      content,
      context: context,
      context_id: context_.id,
      member_id: user.id,
    });

    // Add new note to the current task's notes
    if (notes) {
        notes.push(response.data.note);
    }

    note = ""; // reset textarea after submit
  } catch (error) {
    console.error("Error adding note:", error);
    alert("Failed to add note. Please try again.");
  }
}


