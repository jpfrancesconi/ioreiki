import React, { Component } from 'react'; //Importamos react

import './styles/AuthorList.css';
import defaultAuthor from '../images/default/writter_default.svg';
import AuthorCard from './AuthorCard';

class AuthorList extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      data: {
        loading: true,
        error: null,
        results: [],
        autorData: null,
        openDetails: false,
      },
    }
  }

  componentDidMount() {
    //this.fetchAutores();
    this.setState({ loading: true, error: null });
    fetch('http://localhost/biblioneet/api/autores')
      .then(res => res.json())
      .then(data => {
        this.setState({
          loading: false,
          data: {results: data},
        });
      });
  }

  render() {
    if (this.state.loading){
      return (
        <h3>Cargando...</h3>
      );
    }

    return(
      <div className="AuthorList__container">
      <ul className="AuthorList__list">
        {this.state.data.results.map((autor) => {
          return(
            <li key={autor.nid} className="AuthorList__list-item">
              <a href="#" onClick={this.handleClick.bind(this, autor)}>
                <img src={defaultAuthor} alt="autor"/>
                <span>{autor.apellidos}, {autor.nombres}</span>
              </a>
            </li>
          )}
        )}
      </ul>
        {this.state.openDetails &&(
          <AuthorCard firstName={this.state.autorData.nombres} lastName={this.state.autorData.apellidos} isOpenDetails={this.state.openDetails}/>
      )}
      </div>
    );
  }

  /**
   * Add Autor data to state when clicking on an autor link.
   *
   * @param autor
   * @param e
   */
  handleClick(autor, e) {
    e.preventDefault();
    this.setState({
      autorData: autor,
      openDetails: true,
    });
  }
}

export default AuthorList;
