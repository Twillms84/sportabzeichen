export function init() {
    console.log('Sportabzeichen results.js geladen');

    document.addEventListener('change', (e) => {

        if (!e.target.classList.contains('result-input')) {
            return;
        }

        const input = e.target;
        const td = input.closest('td');

        const epId = input.dataset.ep;
        const leistung = input.value;

        const select = td.querySelector('.discipline-select');
        const disciplineId = select.value;

        if (!disciplineId || leistung === '') {
            return;
        }

        fetch(IServ.routes.resolve('sportabzeichen_results_save_many'), {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify([{
                ep_id: epId,
                discipline_id: disciplineId,
                leistung: leistung
            }])
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                console.error('Speichern fehlgeschlagen', data);
            }
        })
        .catch(err => console.error(err));
    });
}
