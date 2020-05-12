import React from 'react';

import './styles/AuthorCard.css';
import authorPic from '../images/authors/jorgeluisborges.jpeg';

class AuthorCard extends React.Component {

  /**
   * Returns article data from state and displays list.
   * @param e
   */
  handleReturnClick(e) {
    e.preventDefault();
    alert('Se presiono salir');
  }

  render() {
    if (!this.props.isOpenDetails){
      return;
    }
    return (
      <div className="AuthorCard__container">
        <h1 className="AuthorCard__title">Detalles del Autor</h1><a href="#" onClick={this.handleReturnClick.bind(this)}>X</a>
        <div className="AuthorCard__picture">
          <figure>
            <img src={authorPic} alt="author"></img>
          </figure>
        </div>
        <div className="AuthorCard__info">
          <h3>{this.props.firstName}, {this.props.lastName}</h3>
        </div>
      </div >
    )
  }
}

export default AuthorCard;
