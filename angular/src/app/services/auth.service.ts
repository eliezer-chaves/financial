import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap, catchError, throwError } from 'rxjs';
import { Router } from '@angular/router';
import { environment } from '../../enviroments/enviroment';
import { iUserRegister, iUserLogin, iUser } from '../interfaces/iUser.interface';

@Injectable({ providedIn: 'root' })
export class AuthService {

    private API_URL = environment.api_url;
    private currentUserSubject = new BehaviorSubject<iUser | null>(null);
    public currentUser$ = this.currentUserSubject.asObservable();

    constructor(
        private http: HttpClient,
        private router: Router
    ) { 
        this.checkAuth();
    }

    registerUser(data: iUserRegister): Observable<any> {
        const mappedData = {
            usr_first_name: data.usr_first_name,
            usr_last_name: data.usr_last_name,
            usr_email: data.usr_email,
            usr_password: data.usr_password,
            usr_password_confirmation: data.usr_password_confirmation,
            usr_state: data.usr_state,
            usr_country: data.usr_country
        };

        return this.http.post<any>(`${this.API_URL}/register`, mappedData, { withCredentials: true }).pipe(
            tap(response => {
                if (response.user) {
                    this.currentUserSubject.next(response.user);
                }
            }),
            catchError(error => {
                console.error('Erro no registro:', error);
                return throwError(() => error);
            })
        );
    }

    login(data: iUserLogin): Observable<any> {
        return this.http.post<any>(`${this.API_URL}/login`, data, { withCredentials: true }).pipe(
            tap(response => {
                if (response.user) {
                    this.currentUserSubject.next(response.user);
                }
            }),
            catchError(error => {
                console.error('Erro no login:', error);
                return throwError(() => error);
            })
        );
    }

    me(): Observable<iUser> {
        return this.http.get<iUser>(`${this.API_URL}/me`, { withCredentials: true }).pipe(
            tap(user => {
                this.currentUserSubject.next(user);
            }),
            catchError(error => {
                this.currentUserSubject.next(null);
                return throwError(() => error);
            })
        );
    }

    logout(): Observable<any> {
        return this.http.post<any>(`${this.API_URL}/logout`, {}, { withCredentials: true }).pipe(
            tap(() => {
                this.currentUserSubject.next(null);
                this.router.navigate(['/login']);
            }),
            catchError(error => {
                console.error('Erro no logout:', error);
                this.currentUserSubject.next(null);
                return throwError(() => error);
            })
        );
    }

    checkAuth(): void {
        this.me().subscribe({
            next: () => {},
            error: () => {}
        });
    }

    isAuthenticated(): boolean {
        return this.currentUserSubject.value !== null;
    }

    getCurrentUser(): iUser | null {
        return this.currentUserSubject.value;
    }
}