$(document).ready(function () {
    
    $('#articles').get('paginate.php', {
            pageNumber: 0,
            pageSize: 5,
            type: 'articles'
        },
        function (data) {
            alert('fu');
            //$('#articles').html(data);
        },
        'html');
    
        
});
