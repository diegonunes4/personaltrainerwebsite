var rating_click = function( rating )
{
    var comment_rating = document.getElementById( "mpprecipe_comment_rating" )
    comment_rating.value = rating
    update_rating( rating )
}
var update_rating = function( rating )
{
    var r = document.getElementsByClassName('rating')
    for( i=0; i<r.length; i++ )
    {

        var s_rating = r[i].title
        if( s_rating <= rating )
        {
            var rclass    = "rating rating-full";
            var star_html = "&#9733";
        }
        else
        {
            var rclass    = "rating rating-empty";
            var star_html = "&#9734";
        }
        r[i].innerHTML = star_html
        r[i].className = rclass
    }
}
