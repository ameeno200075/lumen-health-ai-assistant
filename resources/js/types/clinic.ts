export interface DoctorAvailability {
    id: number;
    doctor_id: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
}

export interface Doctor {
    id: number;
    name: string;
    specialty: string;
    bio: string | null;
    availabilities: DoctorAvailability[];
}

export interface Appointment {
    id: number;
    patient_name: string;
    patient_email: string;
    scheduled_at: string;
    reason: string | null;
    status: string;
    reminder_opt_in: boolean;
    doctor: {
        id: number;
        name: string;
        specialty: string;
    };
}

export interface ConversationSummary {
    id: string;
    title: string;
    updated_at: string;
}

export interface ConversationMessage {
    role: 'user' | 'assistant';
    content: string;
}
