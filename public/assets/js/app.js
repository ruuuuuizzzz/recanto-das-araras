(() => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const originalFetch = window.fetch;
    window.fetch = (input, options = {}) => {
        const url = new URL(typeof input === 'string' ? input : input.url, location.href);
        if (url.origin === location.origin && (options.method || 'GET').toUpperCase() !== 'GET') {
            const headers = new Headers(options.headers || {});
            headers.set('X-CSRF-Token', token || ''); headers.set('Accept', 'application/json');
            options = {...options, headers};
        }
        return originalFetch(input, options);
    };
    document.querySelectorAll('form').forEach(form => {
        if (form.method.toLowerCase() === 'post' && !form.elements.csrf) {
            const input = document.createElement('input');input.type='hidden';input.name='csrf';input.value=token;form.append(input);
        }
    });
    document.querySelectorAll('.campo').forEach(container => {
        const label=container.querySelector('label');const field=container.querySelector('input:not([type=hidden]),select,textarea');
        if(label && field && !label.contains(field)){if(!field.id)field.id='field-'+field.name;label.htmlFor=field.id;}
    });
    window.submitAction = (path, id) => {
        const form=document.createElement('form');form.method='post';form.action=path;
        for(const [name,value] of Object.entries({id,csrf:token})){const input=document.createElement('input');input.type='hidden';input.name=name;input.value=value;form.append(input);}
        document.body.append(form);form.submit();
    };
    if(document.body.dataset.perfil==='portaria') document.querySelectorAll('a[href]').forEach(link=>{
        if(/cadastro_(morador|avisos)\.php|\/crud\/excluir/.test(link.getAttribute('href')))link.remove();
    });
    // Legacy action links become POST forms; no state changes occur on GET.
    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]'); if (!link) return;
        const url = new URL(link.href, location.href);
        if (url.origin !== location.origin || !/\/crud\/(excluir(?:_\w+)?|toggle_presenca_visitante)\.php$/.test(url.pathname)) return;
        event.preventDefault();event.stopImmediatePropagation();
        if (!confirm(url.pathname.includes('excluir') ? 'Excluir este registro? Esta ação será registrada no histórico.' : 'Confirmar a mudança de presença?')) return;
        const form = document.createElement('form');form.method='post';form.action=url.pathname;
        url.searchParams.set('csrf',token);
        for (const [name,value] of url.searchParams) {const input=document.createElement('input');input.type='hidden';input.name=name;input.value=value;form.append(input);}
        document.body.append(form);form.submit();
    }, true);
    const state = document.getElementById('form-state');
    if (state) {
        const {values, errors} = JSON.parse(state.textContent);
        for(const [name,value] of Object.entries(values)) {
            if(['csrf','id'].includes(name))continue;
            const field = document.querySelector(`[name="${CSS.escape(name)}"]`);if(field)field.value=value;
        }
        const box=document.createElement('div');box.className='app-alert';box.setAttribute('role','alert');box.textContent='Revise os campos indicados. Seus dados foram preservados.';
        document.querySelector('form')?.prepend(box);
        for(const [name,message] of Object.entries(errors)) {
            const field=document.querySelector(`[name="${CSS.escape(name)}"]`);
            const label=document.createElement('p');label.className='field-error';label.id='error-'+name;label.textContent=message;
            if(field){field.setAttribute('aria-invalid','true');field.setAttribute('aria-describedby',label.id);field.after(label);}else box.append(label);
        }
        document.querySelector('[aria-invalid="true"]')?.focus();
    }
    const resident=document.getElementById('morador_id');
    const blockInput=document.querySelector('[name="bloco"]');
    const apartmentInput=document.querySelector('[name="apartamento"]');
    if(resident && blockInput && apartmentInput) {
        const options=Array.from(resident.options).filter(option=>option.value);
        const placeholder=resident.options[0];
        const hint=document.createElement('p');
        hint.id='resident-unit-hint';hint.setAttribute('role','status');
        hint.style.cssText='font-size:12px;line-height:1.5;color:#63776f;margin:8px 0 0';
        resident.after(hint);
        resident.setAttribute('aria-describedby', [resident.getAttribute('aria-describedby'),hint.id].filter(Boolean).join(' '));
        const normalize=value=>value.trim().toLocaleLowerCase('pt-BR');
        function syncUnit() {
            const block=normalize(blockInput.value);
            const apartment=normalize(apartmentInput.value);
            const complete=Boolean(block && apartment);
            const matches=options.filter(option=>complete && normalize(option.dataset.bloco)===block && normalize(option.dataset.apartamento)===apartment);
            for(const option of options) {
                const available=!complete || matches.includes(option);
                option.hidden=!available;option.disabled=!available;
            }
            if(!complete || !matches.some(option=>option.value===resident.value))resident.value='';
            if(matches.length===1)resident.value=matches[0].value;
            placeholder.textContent=!complete ? 'Selecione o morador responsável' : matches.length ? 'Selecione o morador desta unidade' : 'Nenhum morador encontrado';
            hint.textContent=!complete ? 'Informe o bloco e o apartamento para preencher o morador.' : !matches.length ? 'Nenhum morador cadastrado neste bloco e apartamento. Confira os dados informados.' : matches.length===1 ? 'Morador preenchido automaticamente pela unidade.' : 'Esta unidade tem mais de um morador. Selecione o responsável pelo registro.';
        }
        function syncResident() {
            const option=resident.selectedOptions[0];
            if(option?.value) {
                blockInput.value=option.dataset.bloco;
                apartmentInput.value=option.dataset.apartamento;
            }
            syncUnit();
        }
        resident.addEventListener('change',syncResident);
        for(const input of [blockInput,apartmentInput]) {
            input.addEventListener('input',syncUnit);
            input.addEventListener('change',syncUnit);
        }
        if(resident.value)syncResident();else syncUnit();
    }
    document.querySelectorAll('.grupoHeader').forEach(el=>{el.tabIndex=0;el.setAttribute('role','button');el.setAttribute('aria-expanded',String(el.parentElement.classList.contains('aberto')));el.addEventListener('click',()=>el.setAttribute('aria-expanded',String(el.parentElement.classList.contains('aberto'))));el.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();el.click();}});});
})();
