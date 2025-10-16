import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, Router, UrlTree } from '@angular/router';
import { Observable, map, take, filter } from 'rxjs';
import { AuthService } from '../services/auth.service';

@Injectable({
    providedIn: 'root'
})
export class AuthGuard implements CanActivate {

    constructor(
        private authService: AuthService,
        private router: Router
    ) { }

    canActivate(
        route: ActivatedRouteSnapshot,
        state: RouterStateSnapshot
    ): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {
        
        return this.authService.currentUser$.pipe(
            take(1),
            map(user => {
                if (user) {
                    return true;
                }

                // Guarda a URL que o usuário tentou acessar
                const returnUrl = state.url;
                
                // Redireciona para login com a URL de retorno
                return this.router.createUrlTree(['/login'], {
                    queryParams: { returnUrl }
                });
            })
        );
    }
}