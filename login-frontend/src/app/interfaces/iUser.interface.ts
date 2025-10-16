// user.interface.ts
export interface iUserRegister {
    usr_first_name: string;
    usr_last_name: string;
    usr_email: string;
    usr_password: string;
    usr_password_confirmation: string;
    usr_state: string;
    usr_country: string;
}

export interface iUserLogin {
    usr_email: string;
    usr_password: string;
}

export interface iUser {
    id: number;
    usr_first_name: string;
    usr_last_name: string;
    usr_email: string;
    usr_state: string;
    usr_country: string;
    created_at?: string;
    updated_at?: string;
}